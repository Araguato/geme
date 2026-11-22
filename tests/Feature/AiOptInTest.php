<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiContextBuilder;
use App\Services\Ai\AiException;
use App\Services\Ai\AiSettings;
use App\Services\Ai\AiUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiOptInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        // DNS simulado: api.proveedor.test es público; interno.test resuelve a red privada
        AiUrlGuard::$resolver = fn (string $host) => match ($host) {
            'api.proveedor.test' => ['203.0.113.10'],
            'interno.test' => ['10.0.0.5'],
            default => [],
        };
    }

    protected function tearDown(): void
    {
        AiUrlGuard::$resolver = null;
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['name' => 'Admin Tienda']);
        $u->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

        return $u;
    }

    private function enableOwn(User $admin, array $extra = [])
    {
        return $this->actingAs($admin)->post('/settings/ai', array_merge([
            'mode' => 'own',
            'base_url' => 'https://api.proveedor.test/v1',
            'model' => 'modelo-pequeno',
            'api_key' => 'sk-secreta-1234',
            'data_level' => 'aggregate',
            'consent' => '1',
        ], $extra));
    }

    private function fakeProvider(): void
    {
        Http::fake([
            'api.proveedor.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'OK']]],
                'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 2],
            ]),
        ]);
    }

    public function test_ai_is_off_by_default_and_sends_nothing(): void
    {
        $this->assertSame('off', AiSettings::mode());
        $this->assertFalse(AiSettings::enabled());

        $this->expectException(AiException::class);
        AiClient::chat([['role' => 'user', 'content' => 'hola']], 'test');
    }

    public function test_only_admin_can_open_ai_settings(): void
    {
        foreach (['contable', 'supervisor', 'vendedor', 'almacenista'] as $role) {
            $this->actingAs($this->user($role))->get('/settings/ai')->assertForbidden();
        }
        $this->actingAs($this->user('admin'))->get('/settings/ai')
            ->assertOk()
            ->assertSee('Ejemplo real de lo que se enviaría')
            ->assertSee('Todavía no se ha enviado nada');
    }

    public function test_enabling_requires_consent(): void
    {
        $admin = $this->user('admin');
        $this->enableOwn($admin, ['consent' => null])->assertSessionHasErrors('consent');
        $this->assertSame('off', AiSettings::mode());
    }

    public function test_key_is_stored_encrypted_never_shown_and_consent_recorded(): void
    {
        $admin = $this->user('admin');
        $this->enableOwn($admin)->assertRedirect('/settings/ai');

        $raw = Setting::where('key', 'ai_api_key')->value('value');
        $this->assertNotSame('sk-secreta-1234', $raw);
        $this->assertStringNotContainsString('sk-secreta', $raw);
        $this->assertSame('sk-secreta-1234', AiSettings::apiKey());
        $this->assertTrue(AiSettings::enabled());
        $this->assertSame((string) $admin->id, AiSettings::consent()['user_id']);

        $page = $this->actingAs($admin)->get('/settings/ai')->assertOk();
        $page->assertDontSee('sk-secreta-1234')->assertSee('1234');

        // La auditoría registra el cambio pero nunca la clave
        $audit = AuditLog::where('action', 'ai.settings.update')->firstOrFail();
        $this->assertStringNotContainsString('sk-secreta', json_encode($audit->data_after));
        $this->assertTrue($audit->data_after['key_changed']);
    }

    public function test_changing_data_level_requires_new_consent(): void
    {
        $admin = $this->user('admin');
        $this->enableOwn($admin);

        $this->enableOwn($admin, ['data_level' => 'detailed', 'consent' => null, 'api_key' => ''])
            ->assertSessionHasErrors('consent');
        $this->assertSame('aggregate', AiSettings::dataLevel());

        $this->enableOwn($admin, ['data_level' => 'detailed', 'api_key' => ''])->assertSessionHasNoErrors();
        $this->assertSame('detailed', AiSettings::dataLevel());
        $this->assertTrue(AiSettings::hasValidConsent());
        $this->assertSame('sk-secreta-1234', AiSettings::apiKey(), 'Dejar la clave vacía la mantiene');
    }

    public function test_internal_and_plain_http_urls_are_rejected(): void
    {
        $admin = $this->user('admin');
        $this->enableOwn($admin, ['base_url' => 'https://interno.test/v1'])->assertSessionHasErrors('base_url');
        $this->enableOwn($admin, ['base_url' => 'https://127.0.0.1/v1'])->assertSessionHasErrors('base_url');
        $this->enableOwn($admin, ['base_url' => 'https://169.254.169.254/latest'])->assertSessionHasErrors('base_url');
        $this->enableOwn($admin, ['base_url' => 'http://api.proveedor.test/v1'])->assertSessionHasErrors('base_url');
        $this->assertSame('off', AiSettings::mode());
    }

    public function test_private_urls_allowed_only_when_server_permits_it(): void
    {
        config(['ai.allow_private_urls' => true]);
        $this->enableOwn($this->user('admin'), ['base_url' => 'http://192.168.1.20:11434/v1'])->assertSessionHasNoErrors();
        $this->assertSame('own', AiSettings::mode());
    }

    public function test_connection_test_sends_no_business_data_and_is_logged(): void
    {
        $this->fakeProvider();
        $admin = $this->user('admin');
        $this->enableOwn($admin);

        $this->actingAs($admin)->post('/settings/ai/test')
            ->assertRedirect('/settings/ai')
            ->assertSessionHas('success');

        Http::assertSent(function (HttpRequest $req) {
            return $req->url() === 'https://api.proveedor.test/v1/chat/completions'
                && $req->hasHeader('Authorization', 'Bearer sk-secreta-1234')
                && $req['model'] === 'modelo-pequeno'
                && !str_contains(json_encode($req['messages']), 'venta');
        });

        $log = AiUsageLog::firstOrFail();
        $this->assertSame(['ok', 'test', 22, 'api.proveedor.test'], [$log->status, $log->purpose, $log->input_tokens + $log->output_tokens, $log->provider_host]);
    }

    public function test_provider_errors_are_shown_without_leaking_the_key(): void
    {
        Http::fake(['api.proveedor.test/*' => Http::response(['error' => 'invalid key sk-secreta-1234'], 401)]);
        $admin = $this->user('admin');
        $this->enableOwn($admin);

        $this->actingAs($admin)->post('/settings/ai/test')->assertSessionHas('error');
        $this->assertStringNotContainsString('sk-secreta', session('error'));
        $this->assertSame('error', AiUsageLog::first()->status);
    }

    public function test_disable_purges_key_url_and_consent(): void
    {
        $admin = $this->user('admin');
        $this->enableOwn($admin);

        $this->actingAs($admin)->post('/settings/ai/disable')->assertRedirect('/settings/ai');

        $this->assertSame('off', AiSettings::mode());
        $this->assertNull(Setting::where('key', 'ai_api_key')->first());
        $this->assertNull(Setting::where('key', 'ai_base_url')->first());
        $this->assertFalse(AiSettings::hasValidConsent());
        $this->assertTrue(AuditLog::where('action', 'ai.disable')->exists());
    }

    public function test_managed_mode_only_when_server_configured_and_respects_monthly_limit(): void
    {
        $admin = $this->user('admin');
        $this->enableOwn($admin, ['mode' => 'managed'])->assertSessionHasErrors('mode');

        config([
            'ai.managed.base_url' => 'https://api.proveedor.test/v1',
            'ai.managed.api_key' => 'sk-gestionada',
            'ai.managed.model' => 'modelo-gestionado',
            'ai.managed.monthly_token_limit' => 100,
        ]);
        $this->fakeProvider();

        $this->actingAs($admin)->post('/settings/ai', ['mode' => 'managed', 'data_level' => 'aggregate', 'consent' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('managed', AiSettings::mode());

        AiClient::chat([['role' => 'user', 'content' => 'x']], 'resumen', $admin->id);
        Http::assertSent(fn (HttpRequest $r) => $r->hasHeader('Authorization', 'Bearer sk-gestionada'));

        AiUsageLog::create(['mode' => 'managed', 'purpose' => 'resumen', 'status' => 'ok', 'input_tokens' => 100]);
        $this->expectException(AiException::class);
        AiClient::chat([['role' => 'user', 'content' => 'x']], 'resumen', $admin->id);
    }

    public function test_context_never_contains_customer_or_employee_personal_data(): void
    {
        $seller = $this->user('vendedor');
        $seller->update(['name' => 'Ian Pérez']);
        $category = Category::create(['name' => 'Víveres', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Harina', 'price' => 10, 'cost' => 6, 'is_active' => true]);
        $order = Order::create([
            'order_number' => 'T1', 'user_id' => $seller->id, 'type' => 'pos', 'status' => 'completed', 'payment_status' => 'paid',
            'customer_name' => 'María Cliente', 'customer_email' => 'maria@cliente.test', 'customer_phone' => '04141234567',
            'subtotal' => 20, 'tax' => 0, 'total' => 20,
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'name' => 'Harina', 'quantity' => 2, 'unit_price' => 10, 'tax_amount' => 0, 'total' => 20]);

        foreach (['aggregate', 'detailed'] as $level) {
            $json = json_encode(AiContextBuilder::build($level), JSON_UNESCAPED_UNICODE);
            foreach (['María', 'maria@cliente.test', '04141234567', 'Ian', 'Pérez'] as $personal) {
                $this->assertStringNotContainsString($personal, $json, "$level no debe incluir $personal");
            }
            $this->assertStringContainsString('Víveres', $json);
        }

        $detailed = AiContextBuilder::build('detailed');
        $this->assertSame('Vendedor 1', $detailed['vendedores_mes'][0]['vendedor']);
        $this->assertArrayNotHasKey('vendedores_mes', AiContextBuilder::build('aggregate'));
    }
}
