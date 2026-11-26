<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\Category;
use App\Models\FiscalDocument;
use App\Models\FiscalLedger;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Party;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Fiscal\FiscalEmissionService;
use App\Services\Fiscal\FiscalPayloadBuilder;
use App\Services\Fiscal\RifValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalEmissionTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

        return $u;
    }

    private function order(float $price = 10, float $taxRate = 16, ?Party $party = null): Order
    {
        $o = Order::create([
            'order_number' => 'ORD-' . random_int(100000, 999999), 'type' => 'pos', 'status' => 'completed',
            'payment_status' => 'paid', 'subtotal' => $price, 'tax' => round($price * $taxRate / 100, 2),
            'total' => $price + round($price * $taxRate / 100, 2), 'exchange_rate' => 40.5,
            'customer_party_id' => $party?->id,
        ]);
        OrderItem::create([
            'order_id' => $o->id, 'name' => 'Cable', 'quantity' => 1, 'unit_price' => $price,
            'tax_rate' => $taxRate, 'tax_amount' => round($price * $taxRate / 100, 2), 'total' => $price * (1 + $taxRate / 100),
        ]);

        return $o;
    }

    public function test_por_defecto_no_hay_medio_y_la_venta_guarda_la_tasa(): void
    {
        Setting::set('bcv_rate', '40.5');
        $user = $this->user('cajero');
        $cat = Category::create(['name' => 'General', 'is_active' => true]);
        $w = Warehouse::create(['code' => 'MAIN', 'name' => 'P', 'is_active' => true]);
        $l = Location::create(['warehouse_id' => $w->id, 'code' => 'G', 'name' => 'G', 'is_active' => true]);
        $p = Product::create(['category_id' => $cat->id, 'name' => 'Cable', 'price' => 5, 'is_active' => true]);
        StockItem::create(['product_id' => $p->id, 'warehouse_id' => $w->id, 'location_id' => $l->id, 'quantity' => 10, 'average_cost' => 2, 'min_quantity' => 0]);
        CashShift::create(['user_id' => $user->id, 'opened_at' => now(), 'opening_amount' => 0, 'is_active' => true]);

        $this->actingAs($user)->post('/pos', [
            'items' => [['product_id' => $p->id, 'quantity' => 1, 'unit_price' => 5]],
            'payment' => ['method' => 'cash', 'amount' => 5],
        ])->assertSessionHasNoErrors();

        $order = Order::latest('id')->first();
        $this->assertEquals(40.5, (float) $order->exchange_rate);
        $this->assertSame(0, FiscalDocument::count()); // sin medio: comportamiento de siempre
    }

    public function test_con_proveedor_de_pruebas_se_crea_el_documento_y_se_emite(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        $order = $this->order();

        $doc = FiscalEmissionService::enqueue($order);
        $this->assertSame('pending', $doc->status);
        $this->assertSame('test', $doc->environment);

        FiscalEmissionService::process($doc);
        $doc->refresh();

        $this->assertSame('issued', $doc->status);
        $this->assertStringStartsWith('PRUEBA-', $doc->invoice_number);
        $this->assertSame($doc->invoice_number, $order->refresh()->external_invoice_number);

        $ledger = FiscalLedger::where('related_type', Order::class)->where('related_id', $order->id)->first();
        $this->assertSame($doc->control_number, $ledger->control_number);
        $this->assertSame($doc->invoice_number, $ledger->document_number);
    }

    public function test_encolar_dos_veces_no_duplica(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        $order = $this->order();
        FiscalEmissionService::enqueue($order);
        FiscalEmissionService::enqueue($order);

        $this->assertSame(1, FiscalDocument::count());
    }

    public function test_el_fallo_del_medio_no_pierde_la_venta_y_se_reintenta(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        Setting::set('fiscal_sandbox_fail', '1');
        $order = $this->order();
        $doc = FiscalEmissionService::enqueue($order);

        FiscalEmissionService::process($doc);
        $doc->refresh();
        $this->assertSame('error', $doc->status);
        $this->assertNotNull($doc->next_attempt_at);
        $this->assertSame(1, $doc->attempts);
        $this->assertNotNull(Order::find($order->id));

        Setting::set('fiscal_sandbox_fail', '0');
        $doc->update(['next_attempt_at' => now()->subMinute()]);
        $this->assertSame(1, FiscalEmissionService::processDue());
        $this->assertSame('issued', $doc->refresh()->status);
    }

    public function test_tras_el_maximo_de_intentos_requiere_intervencion(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        Setting::set('fiscal_sandbox_fail', '1');
        $doc = FiscalEmissionService::enqueue($this->order());
        $doc->update(['attempts' => FiscalEmissionService::MAX_ATTEMPTS - 1]);

        FiscalEmissionService::process($doc->refresh());

        $this->assertNull($doc->refresh()->next_attempt_at);
        $this->assertSame(0, FiscalEmissionService::processDue());
    }

    public function test_contingencia_registra_el_talonario_y_audita(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        $doc = FiscalEmissionService::enqueue($this->order());
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('fiscal-documents.contingency', $doc), [
            'invoice_number' => 'T-0001', 'control_number' => '00-9999',
        ])->assertSessionHasNoErrors();

        $doc->refresh();
        $this->assertSame('contingency', $doc->status);
        $this->assertSame('T-0001', $doc->order->external_invoice_number);
        $this->assertTrue(AuditLog::where('action', 'fiscal_contingency')->exists());

        // ya resuelto: no se puede volver a registrar
        $this->actingAs($admin)->post(route('fiscal-documents.contingency', $doc), [
            'invoice_number' => 'X', 'control_number' => 'Y',
        ])->assertStatus(422);
    }

    public function test_contingencia_se_remite_al_dia_siguiente_y_queda_registrada(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        $admin = $this->user('admin');
        $doc = FiscalEmissionService::enqueue($this->order());
        $this->actingAs($admin)->post(route('fiscal-documents.contingency', $doc), [
            'invoice_number' => 'T-0002', 'control_number' => '00-8888',
        ])->assertSessionHasNoErrors();
        $doc->refresh();

        $this->assertTrue($doc->needsReport());
        $this->assertFalse($doc->isReportOverdue());
        $this->actingAs($admin)->get(route('fiscal-documents.index'))->assertOk()
            ->assertSee('por remitir')->assertSee('Remitir antes del');

        // Vence al terminar el día siguiente
        $this->travel(2)->days();
        $this->assertTrue($doc->fresh()->isReportOverdue());
        $this->actingAs($admin)->get(route('fiscal-documents.index'))->assertSee('vencida');
        $this->travelBack();

        $this->actingAs($admin)->post(route('fiscal-documents.report', $doc))->assertSessionHas('success');
        $doc->refresh();
        $this->assertNotNull($doc->reported_at);
        $this->assertFalse($doc->needsReport());
        $this->assertTrue(AuditLog::where('action', 'fiscal_contingency_reported')->where('entity_id', $doc->id)->exists());

        // Un documento emitido por el medio no se "remite"
        $issued = FiscalEmissionService::enqueue($this->order());
        FiscalEmissionService::process($issued->refresh());
        $this->actingAs($admin)->post(route('fiscal-documents.report', $issued))->assertStatus(422);

        // El cajero no tiene acceso
        $this->actingAs($this->user('cajero'))->post(route('fiscal-documents.report', $doc))->assertForbidden();
    }

    public function test_payload_con_exentos_alicuotas_rif_y_equivalente_en_bs(): void
    {
        $party = Party::create(['type' => 'customer', 'name' => 'ACME', 'document_type' => 'RIF', 'document_number' => 'j-12345678-9', 'is_active' => true]);
        $order = $this->order(100, 16, $party);
        OrderItem::create(['order_id' => $order->id, 'name' => 'Pan', 'quantity' => 2, 'unit_price' => 5, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 10]);

        $p = FiscalPayloadBuilder::build($order->refresh());

        $this->assertSame('J-12345678-9', $p['buyer']['tax_id']);
        $this->assertTrue($p['buyer']['tax_id_valid']);
        $this->assertEquals(10.0, $p['exempt_amount']);
        $this->assertTrue($p['items'][1]['exempt']);
        $this->assertCount(1, $p['tax_by_rate']);
        $this->assertEquals(16.0, $p['tax_by_rate'][0]['tax']);
        $this->assertEquals(126.0, $p['total']);
        $this->assertEquals(round(126 * 40.5, 2), $p['total_bs']);
        $this->assertSame(40.5, $p['exchange_rate']);
    }

    public function test_consumidor_final_sin_rif(): void
    {
        $p = FiscalPayloadBuilder::build($this->order());
        $this->assertSame('CONSUMIDOR FINAL', $p['buyer']['name']);
        $this->assertNull($p['buyer']['tax_id']);
    }

    public function test_formato_de_rif(): void
    {
        $this->assertTrue(RifValidator::isValid('J-12345678-9'));
        $this->assertTrue(RifValidator::isValid('v12345678'));
        $this->assertTrue(RifValidator::isValid('E-81234567'));
        $this->assertFalse(RifValidator::isValid('J-1234'));
        $this->assertFalse(RifValidator::isValid('X-12345678'));
        $this->assertFalse(RifValidator::isValid(''));
        $this->assertFalse(RifValidator::isValid('J-12345678')); // jurídico sin verificador
    }

    public function test_permisos_y_ajustes(): void
    {
        $this->actingAs($this->user('vendedor'))->get('/fiscal-documents')->assertForbidden();

        $contable = $this->user('contable');
        $this->actingAs($contable)->get('/fiscal-documents')->assertOk();
        $this->actingAs($contable)->get('/settings/fiscal')->assertForbidden();

        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/settings/fiscal')->assertOk();
        $this->actingAs($admin)->post('/settings/fiscal', ['provider' => 'inventado'])->assertSessionHasErrors('provider');
        $this->actingAs($admin)->post('/settings/fiscal', ['provider' => 'sandbox'])->assertSessionHasNoErrors();
        $this->assertSame('sandbox', Setting::get('fiscal_provider'));
        $this->assertTrue(AuditLog::where('action', 'fiscal_provider_changed')->exists());
    }

    public function test_ticket_muestra_numero_fiscal_tasa_y_aviso_de_prueba(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        $order = $this->order();
        FiscalEmissionService::process(FiscalEmissionService::enqueue($order));

        $this->actingAs($this->user('admin'))->get(route('pos.ticket', $order))
            ->assertOk()
            ->assertSee('SIN VALIDEZ FISCAL')
            ->assertSee('PRUEBA-')
            ->assertSee('Equivalente en Bs');
    }
}
