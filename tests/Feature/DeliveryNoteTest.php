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
use App\Models\Setting;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryNoteTest extends TestCase
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

    private function sellNote(User $cashier, float $qty = 2): Order
    {
        $this->actingAs($cashier)->post('/pos', [
            'document_kind' => 'nota_entrega',
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_price' => 10]],
            'payment' => ['method' => 'cash', 'amount' => $qty * 10],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        return Order::latest('id')->firstOrFail();
    }

    public function test_delivery_note_moves_stock_and_cash_but_is_not_fiscal(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        $cashier = $this->user('cajero');
        $this->openShift($cashier);

        $order = $this->sellNote($cashier, 2);

        $this->assertTrue($order->is_delivery_note);
        $this->assertStringStartsWith('NE-', $order->delivery_note_number);
        $this->assertSame('completed', $order->status);
        $this->assertEquals(8, (float) $this->stock->fresh()->quantity);
        $this->assertEquals(20, (float) Payment::where('order_id', $order->id)->sum('amount'));

        // Sin libro de ventas ni cola de emisión hasta que se facture
        $this->assertSame(0, FiscalLedger::count());
        $this->assertSame(0, FiscalDocument::count());
    }

    public function test_normal_sale_still_goes_to_the_ledger(): void
    {
        $cashier = $this->user('cajero');
        $this->openShift($cashier);
        $this->actingAs($cashier)->post('/pos', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10]],
            'payment' => ['method' => 'cash', 'amount' => 10],
        ])->assertSessionHasNoErrors();

        $order = Order::latest('id')->first();
        $this->assertFalse($order->is_delivery_note);
        $this->assertNull($order->delivery_note_number);
        $this->assertSame(1, FiscalLedger::count());
    }

    public function test_invoicing_a_delivery_note_creates_the_ledger_entry_and_fiscal_document(): void
    {
        Setting::set('fiscal_provider', 'sandbox');
        $cashier = $this->user('cajero');
        $this->openShift($cashier);
        $order = $this->sellNote($cashier);

        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('delivery-notes.index'))->assertOk()->assertSee($order->delivery_note_number);

        $this->actingAs($admin)->post(route('delivery-notes.invoice', $order))->assertSessionHas('success');

        $order->refresh();
        $this->assertFalse($order->is_delivery_note);
        $this->assertNotNull($order->invoiced_at);
        $this->assertSame($admin->id, (int) $order->invoiced_by);
        $this->assertSame(1, FiscalLedger::where('related_type', Order::class)->where('related_id', $order->id)->count());
        $this->assertSame(now()->toDateString(), FiscalLedger::first()->document_date->toDateString());
        $this->assertSame(1, FiscalDocument::where('order_id', $order->id)->count());
        $this->assertTrue(AuditLog::where('action', 'delivery_note_invoiced')->where('entity_id', $order->id)->exists());

        // No se factura dos veces
        $this->actingAs($admin)->post(route('delivery-notes.invoice', $order))->assertSessionHas('error');
        $this->assertSame(1, FiscalLedger::where('related_id', $order->id)->count());
        $this->actingAs($admin)->get(route('delivery-notes.index'))->assertOk();
    }

    public function test_cashier_cannot_invoice_or_list_delivery_notes(): void
    {
        $cashier = $this->user('cajero');
        $this->openShift($cashier);
        $order = $this->sellNote($cashier);

        $this->actingAs($cashier)->get(route('delivery-notes.index'))->assertForbidden();
        $this->actingAs($cashier)->post(route('delivery-notes.invoice', $order))->assertForbidden();
        $this->assertTrue($order->fresh()->is_delivery_note);
    }

    public function test_voiding_a_pending_delivery_note_restores_stock_without_credit_note_or_ledger(): void
    {
        $admin = $this->user('admin');
        $this->openShift($admin);
        $order = $this->sellNote($admin, 3);
        $this->assertEquals(7, (float) $this->stock->fresh()->quantity);

        $this->actingAs($admin)->post(route('orders.void', $order), ['reason' => 'Cliente no la recibió'])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertEquals(10, (float) $this->stock->fresh()->quantity);
        $this->assertEquals(0, (float) Payment::where('order_id', $order->id)->sum('amount'));
        $this->assertSame(0, CreditNote::count());
        $this->assertSame(0, FiscalLedger::count());
        $this->assertTrue(AuditLog::where('action', 'delivery_note_voided')->exists());

        // Una nota anulada ya no se puede facturar ni sale en el listado
        $this->actingAs($admin)->post(route('delivery-notes.invoice', $order))->assertSessionHas('error');
        $this->assertSame(0, FiscalLedger::count());
    }

    public function test_ticket_shows_delivery_note_and_invoice_button(): void
    {
        $admin = $this->user('admin');
        $this->openShift($admin);
        $order = $this->sellNote($admin);

        $this->actingAs($admin)->get(route('pos.ticket', $order))
            ->assertOk()
            ->assertSee('NOTA DE ENTREGA')
            ->assertSee('Convertir en factura');

        $this->actingAs($admin)->post(route('delivery-notes.invoice', $order));
        $this->actingAs($admin)->get(route('pos.ticket', $order))
            ->assertOk()
            ->assertDontSee('Convertir en factura')
            ->assertSee('antes nota de entrega');
    }
}
