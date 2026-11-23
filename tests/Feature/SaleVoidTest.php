<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\Category;
use App\Models\CreditNote;
use App\Models\FiscalDocument;
use App\Models\FiscalLedger;
use App\Models\Location;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesLocation;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleVoidTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private StockItem $stock;
    private SalesLocation $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $category = Category::create(['name' => 'General', 'is_active' => true]);
        $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Principal', 'is_active' => true]);
        $location = Location::create(['warehouse_id' => $warehouse->id, 'code' => 'G', 'name' => 'General', 'is_active' => true]);
        $this->shop = SalesLocation::create(['name' => 'Tienda', 'warehouse_id' => $warehouse->id, 'is_active' => true]);
        $this->product = Product::create(['category_id' => $category->id, 'name' => 'Salsa', 'price' => 10, 'is_active' => true]);
        $this->stock = StockItem::create([
            'product_id' => $this->product->id, 'warehouse_id' => $warehouse->id, 'location_id' => $location->id,
            'quantity' => 10, 'average_cost' => 2,
        ]);
        InventoryService::syncStockQuantity($this->product);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

        return $user;
    }

    private function openShift(User $user): CashShift
    {
        return CashShift::create(['user_id' => $user->id, 'sales_location_id' => $this->shop->id, 'opened_at' => now(), 'opening_amount' => 0, 'is_active' => true]);
    }

    private function sell(User $cashier, float $qty = 3): Order
    {
        $this->actingAs($cashier)->post('/pos', [
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_price' => 10]],
            'payment' => ['method' => 'cash', 'amount' => $qty * 10],
        ])->assertSessionHasNoErrors();

        return Order::latest('id')->firstOrFail();
    }

    public function test_admin_voids_a_sale_and_everything_is_reverted_and_audited(): void
    {
        $cashier = $this->user('cajero');
        $this->openShift($cashier);
        $order = $this->sell($cashier, 3);
        $this->assertEquals(7, (float) $this->stock->fresh()->quantity);

        $admin = $this->user('admin');
        $adminShift = $this->openShift($admin);

        $this->actingAs($admin)
            ->post(route('orders.void', $order), ['reason' => 'Error de digitación'])
            ->assertRedirect(route('pos.ticket', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame($admin->id, (int) $order->voided_by);
        $this->assertSame('Error de digitación', $order->void_reason);

        // Inventario devuelto y con su movimiento en el kardex
        $this->assertEquals(10, (float) $this->stock->fresh()->quantity);
        $this->assertEquals(10, (float) $this->product->fresh()->stock_quantity);
        $this->assertSame(1, StockMovement::where('reference_type', 'order')->where('reference_id', $order->id)->where('reason', 'sale_void')->count());

        // Cobro devuelto en la caja de quien anula; el neto de la venta es cero
        $refund = Payment::where('order_id', $order->id)->where('amount', '<', 0)->first();
        $this->assertNotNull($refund);
        $this->assertEquals(-1 * (float) $order->total, (float) $refund->amount);
        $this->assertSame($adminShift->id, (int) $refund->cash_shift_id);
        $this->assertEquals(0, (float) Payment::where('order_id', $order->id)->sum('amount'));

        // Nota de crédito y su asiento en el libro; el asiento de la venta sigue intacto
        $note = CreditNote::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('applied', $note->status);
        $this->assertEquals((float) $order->total, (float) $note->amount);
        $this->assertSame(1, $note->items()->count());
        $this->assertSame($order->order_number, $note->affected_document_number);

        $this->assertSame(1, FiscalLedger::where('related_type', Order::class)->where('related_id', $order->id)->count());
        $ledgerNote = FiscalLedger::where('related_type', CreditNote::class)->where('related_id', $note->id)->first();
        $this->assertNotNull($ledgerNote);
        $this->assertSame('NOTA_CREDITO', $ledgerNote->document_type);
        $this->assertSame($order->order_number, $ledgerNote->affected_document_number);
        $this->assertEquals((float) $order->total, (float) $ledgerNote->total_amount);

        $this->assertTrue(AuditLog::where('action', 'sale_voided')->where('entity_id', $order->id)->exists());
    }

    public function test_a_voided_sale_cannot_be_voided_twice_and_stock_is_restored_once(): void
    {
        $admin = $this->user('admin');
        $this->openShift($admin);
        $order = $this->sell($admin, 2);

        $this->actingAs($admin)->post(route('orders.void', $order), ['reason' => 'Cliente devolvió'])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('orders.void', $order), ['reason' => 'Otra vez'])->assertSessionHas('error');

        $this->assertEquals(10, (float) $this->stock->fresh()->quantity);
        $this->assertSame(1, CreditNote::where('order_id', $order->id)->count());
    }

    public function test_cashier_and_unauthenticated_users_cannot_void(): void
    {
        $cashier = $this->user('cajero');
        $this->openShift($cashier);
        $order = $this->sell($cashier);

        $this->actingAs($cashier)->post(route('orders.void', $order), ['reason' => 'Quiero anularla'])->assertForbidden();
        $this->assertSame('completed', $order->fresh()->status);

        auth()->logout();
        $this->post(route('orders.void', $order), ['reason' => 'Sin sesión'])->assertRedirect('/login');
    }

    public function test_reason_is_required(): void
    {
        $admin = $this->user('admin');
        $this->openShift($admin);
        $order = $this->sell($admin);

        $this->actingAs($admin)->post(route('orders.void', $order), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_refund_requires_an_open_shift(): void
    {
        $cashier = $this->user('cajero');
        $shift = $this->openShift($cashier);
        $order = $this->sell($cashier);
        $shift->update(['is_active' => false, 'closed_at' => now()]);

        $admin = $this->user('admin'); // sin turno abierto

        $this->actingAs($admin)->post(route('orders.void', $order), ['reason' => 'Anulación sin caja'])->assertSessionHas('error');
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertEquals(7, (float) $this->stock->fresh()->quantity);
    }

    public function test_sale_with_real_fiscal_invoice_is_refused_and_pending_document_is_cancelled(): void
    {
        $admin = $this->user('admin');
        $this->openShift($admin);

        $issued = $this->sell($admin, 1);
        FiscalDocument::create([
            'order_id' => $issued->id, 'document_type' => 'FACTURA', 'status' => 'issued', 'provider' => 'imprenta_x',
            'invoice_number' => '00001', 'control_number' => '00-00001',
        ]);
        $this->actingAs($admin)->post(route('orders.void', $issued), ['reason' => 'Factura ya emitida'])->assertSessionHas('error');
        $this->assertSame('completed', $issued->fresh()->status);

        $pending = $this->sell($admin, 1);
        $doc = FiscalDocument::create([
            'order_id' => $pending->id, 'document_type' => 'FACTURA', 'status' => 'pending', 'provider' => 'sandbox',
        ]);
        $this->actingAs($admin)->post(route('orders.void', $pending), ['reason' => 'Aún sin emitir'])->assertSessionHas('success');
        $this->assertSame('cancelled', $doc->fresh()->status);
    }

    public function test_voided_sales_leave_the_sales_reports(): void
    {
        $admin = $this->user('admin');
        $this->openShift($admin);
        $order = $this->sell($admin, 2);

        $this->actingAs($admin)->post(route('orders.void', $order), ['reason' => 'Prueba de informes'])->assertSessionHas('success');

        $this->assertSame(0, Order::where('status', 'completed')->count());
        $this->actingAs($admin)->get(route('pos.ticket', $order))->assertOk()->assertSee('VENTA ANULADA');
    }
}
