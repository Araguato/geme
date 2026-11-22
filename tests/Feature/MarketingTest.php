<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Party;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerInsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $attrs = []): User
    {
        $u = User::factory()->create($attrs);
        $u->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

        return $u;
    }

    private function customer(string $name, bool $optIn = false, ?string $phone = '0414-1234567'): Party
    {
        $p = Party::create(['type' => 'customer', 'name' => $name, 'document_type' => 'CI', 'document_number' => (string) random_int(1000000, 9999999), 'phone' => $phone, 'is_active' => true]);
        if ($optIn) {
            $p->setMarketingConsent(true, 'ficha');
        }

        return $p;
    }

    private function sale(Party $party, float $net, int $daysAgo, ?Product $product = null, string $status = 'completed'): Order
    {
        $o = Order::create([
            'order_number' => uniqid('T'), 'customer_party_id' => $party->id, 'type' => 'pos', 'status' => $status,
            'payment_status' => 'paid', 'subtotal' => $net, 'tax' => 0, 'total' => $net,
        ]);
        $o->created_at = now()->subDays($daysAgo);
        $o->save();
        if ($product) {
            OrderItem::create(['order_id' => $o->id, 'product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_price' => $net, 'tax_amount' => 0, 'total' => $net]);
        }

        return $o;
    }

    public function test_only_admin_and_supervisor_can_access_marketing(): void
    {
        $party = $this->customer('Ana');
        foreach (['vendedor', 'cajero', 'almacenista', 'contable', 'cliente'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get('/marketing')->assertForbidden();
            $this->actingAs($u)->get('/marketing/exportar')->assertForbidden();
            $this->actingAs($u)->get("/marketing/clientes/{$party->id}")->assertForbidden();
        }
        $this->actingAs($this->user('supervisor'))->get('/marketing')->assertOk()->assertSee('Marketing');
        $this->actingAs($this->user('admin'))->get("/marketing/clientes/{$party->id}")->assertOk()->assertSee('Ana');
    }

    public function test_segments_are_computed_from_completed_sales(): void
    {
        $cat = Category::create(['name' => 'Víveres', 'is_active' => true]);
        $harina = Product::create(['category_id' => $cat->id, 'name' => 'Harina', 'price' => 1, 'is_active' => true]);

        $frecuente = $this->customer('Frecuente');
        foreach ([2, 10, 20, 40, 120] as $d) {
            $this->sale($frecuente, 10, $d, $harina);
        }
        $nuevo = $this->customer('Nuevo');
        $this->sale($nuevo, 5, 3);
        $riesgo = $this->customer('Riesgo');
        $this->sale($riesgo, 5, 60);
        $inactivo = $this->customer('Inactivo');
        $this->sale($inactivo, 5, 200);
        $this->sale($inactivo, 5, 1, null, 'cancelled'); // anulada: no cuenta
        $sinCompras = $this->customer('Sin compras');

        $rows = (new CustomerInsightsService())->customers()->keyBy('name');

        $this->assertContains('frecuentes', $rows['Frecuente']['segments']);
        $this->assertContains('vip', $rows['Frecuente']['segments']);
        $this->assertSame('Víveres', $rows['Frecuente']['favorite_category']);
        $this->assertSame(5, $rows['Frecuente']['orders']);
        $this->assertContains('nuevos', $rows['Nuevo']['segments']);
        $this->assertContains('en_riesgo', $rows['Riesgo']['segments']);
        $this->assertContains('inactivos', $rows['Inactivo']['segments']);
        $this->assertSame(1, $rows['Inactivo']['orders']);
        $this->assertSame(['sin_compras'], $rows['Sin compras']['segments']);

        $admin = $this->user('admin');
        $names = collect($this->actingAs($admin)->get('/marketing?segmento=frecuentes')->viewData('customers')->items())->pluck('name');
        $this->assertSame(['Frecuente'], $names->all());

        $byCategory = collect($this->actingAs($admin)->get("/marketing?category_id={$cat->id}")->viewData('customers')->items())->pluck('name');
        $this->assertSame(['Frecuente'], $byCategory->all());
    }

    public function test_export_only_includes_customers_with_consent_and_is_audited(): void
    {
        $this->sale($this->customer('Con permiso', true), 10, 5);
        $this->sale($this->customer('Sin permiso', false), 10, 5);
        $this->customer('=Formula', true);
        $admin = $this->user('admin');

        $csv = $this->actingAs($admin)->get('/marketing/exportar')->assertOk()->streamedContent();

        $this->assertStringContainsString('Con permiso', $csv);
        $this->assertStringNotContainsString('Sin permiso', $csv);
        $this->assertStringContainsString("'=Formula", $csv);
        $this->assertStringContainsString('584141234567', $csv);

        $log = AuditLog::where('action', 'marketing.export')->firstOrFail();
        $this->assertSame(2, $log->data_after['count']);
    }

    public function test_whatsapp_links_only_for_consenting_customers(): void
    {
        $this->customer('Luisa Gómez', true);
        $this->customer('Pedro Pérez', false);
        $admin = $this->user('admin');

        $html = $this->actingAs($admin)->get('/marketing?mensaje=' . urlencode('Hola {nombre}, oferta'))->assertOk()->getContent();

        $this->assertStringContainsString('https://wa.me/584141234567?text=' . rawurlencode('Hola Luisa, oferta'), $html);
        $this->assertSame(1, substr_count($html, 'https://wa.me/'));
        $this->assertStringContainsString('Sin consentimiento', $html);
    }

    public function test_consent_can_be_granted_and_revoked_with_audit(): void
    {
        $party = $this->customer('Ana');
        $admin = $this->user('admin');

        $this->actingAs($admin)->post("/marketing/clientes/{$party->id}/consentimiento", ['opt_in' => 1])->assertRedirect();
        $party->refresh();
        $this->assertTrue($party->marketing_opt_in);
        $this->assertSame('ficha', $party->marketing_opt_in_source);
        $this->assertNotNull($party->marketing_opt_in_at);

        $this->actingAs($admin)->post("/marketing/clientes/{$party->id}/consentimiento", ['opt_in' => 0]);
        $party->refresh();
        $this->assertFalse($party->marketing_opt_in);
        $this->assertNull($party->marketing_opt_in_at);

        $this->assertSame(2, AuditLog::where('entity_type', 'party')->where('entity_id', $party->id)->count());
    }

    public function test_customer_created_from_pos_can_record_consent(): void
    {
        $this->actingAs($this->user('cajero'))->post('/parties?redirect=pos', [
            'type' => 'customer', 'name' => 'Cliente TPV', 'document_type' => 'CI', 'document_number' => '12345678',
            'phone' => '04121112233', 'marketing_opt_in' => '1',
        ])->assertRedirect('/pos');

        $party = Party::where('name', 'Cliente TPV')->firstOrFail();
        $this->assertTrue($party->marketing_opt_in);
        $this->assertSame('tpv', $party->marketing_opt_in_source);
    }

    public function test_online_order_is_linked_to_customer_and_copies_web_consent(): void
    {
        $cat = Category::create(['name' => 'Víveres', 'is_active' => true]);
        $product = Product::create(['category_id' => $cat->id, 'name' => 'Arroz', 'price' => 2, 'is_active' => true]);
        $client = $this->user('cliente', ['email' => 'Maria@Correo.test', 'marketing_opt_in' => true]);

        $this->actingAs($client)
            ->withSession(['cart' => [$product->id => ['product_id' => $product->id, 'name' => 'Arroz', 'price' => 2, 'quantity' => 1]]])
            ->post('/pedido', ['customer_name' => 'María', 'customer_phone' => '04140000000', 'address' => 'Calle 1'])
            ->assertRedirect();

        $party = Party::where('email', 'maria@correo.test')->firstOrFail();
        $this->assertSame('customer', $party->type);
        $this->assertTrue($party->marketing_opt_in);
        $this->assertSame('registro_web', $party->marketing_opt_in_source);
        $this->assertSame($party->id, Order::where('type', 'online')->first()->customer_party_id);

        // Segundo pedido: reutiliza la misma ficha
        $this->actingAs($client)
            ->withSession(['cart' => [$product->id => ['product_id' => $product->id, 'name' => 'Arroz', 'price' => 2, 'quantity' => 1]]])
            ->post('/pedido', ['customer_name' => 'María', 'customer_phone' => '04140000000', 'address' => 'Calle 1']);
        $this->assertSame(1, Party::customers()->count());
    }

    public function test_whatsapp_number_normalization(): void
    {
        $this->assertSame('584141234567', CustomerInsightsService::whatsappNumber('0414-123.45.67'));
        $this->assertSame('584141234567', CustomerInsightsService::whatsappNumber('+58 414 1234567'));
        $this->assertSame('34612345678', CustomerInsightsService::whatsappNumber('0034 612 345 678'));
        $this->assertNull(CustomerInsightsService::whatsappNumber('123'));
        $this->assertNull(CustomerInsightsService::whatsappNumber(null));
    }
}
