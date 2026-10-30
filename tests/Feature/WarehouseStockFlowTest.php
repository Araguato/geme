<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Category;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Role;
use App\Models\SalesLocation;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarehouseStockFlowTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Warehouse $sabor;
    private Warehouse $cosmo;
    private Location $saborShelf1;
    private Location $saborShelf2;
    private Location $cosmoShelf;
    private SalesLocation $saborShop;
    private SalesLocation $cosmoShop;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'General', 'is_active' => true]);
        $this->sabor = Warehouse::create(['code' => 'SAB', 'name' => 'Sabor', 'is_active' => true]);
        $this->cosmo = Warehouse::create(['code' => 'COS', 'name' => 'Cosmonauta8', 'is_active' => true]);
        $this->saborShelf1 = Location::create(['warehouse_id' => $this->sabor->id, 'code' => 'S1', 'name' => 'Estante 1', 'is_active' => true]);
        $this->saborShelf2 = Location::create(['warehouse_id' => $this->sabor->id, 'code' => 'S2', 'name' => 'Estante 2', 'is_active' => true]);
        $this->cosmoShelf = Location::create(['warehouse_id' => $this->cosmo->id, 'code' => 'C1', 'name' => 'Estante', 'is_active' => true]);
        $this->saborShop = SalesLocation::create(['name' => 'Sabor', 'warehouse_id' => $this->sabor->id, 'is_active' => true]);
        $this->cosmoShop = SalesLocation::create(['name' => 'Cosmonauta8', 'warehouse_id' => $this->cosmo->id, 'is_active' => true]);
    }

    private function user(string $role = 'cajero'): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

        return $user;
    }

    private function product(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => 'Salsa',
            'price' => 10,
            'is_active' => true,
        ], $attrs));
    }

    private function stock(Product $product, Location $location, float $quantity): StockItem
    {
        $item = StockItem::create([
            'product_id' => $product->id,
            'warehouse_id' => $location->warehouse_id,
            'location_id' => $location->id,
            'quantity' => $quantity,
            'average_cost' => 2,
        ]);
        InventoryService::syncStockQuantity($product);

        return $item;
    }

    private function openShift(User $user, SalesLocation $shop): CashShift
    {
        return CashShift::create(['user_id' => $user->id, 'sales_location_id' => $shop->id, 'opened_at' => now(), 'opening_amount' => 0, 'is_active' => true]);
    }

    private function sell(User $user, Product $product, float $qty, array $extra = [])
    {
        return $this->actingAs($user)->from('/pos')->post('/pos', [
            'items' => [array_merge(['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => 10], $extra)],
            'payment' => ['method' => 'cash', 'amount' => $qty * 10],
        ]);
    }

    public function test_each_cashier_sells_from_their_own_shop_with_both_shifts_open(): void
    {
        $saborCashier = $this->user();
        $cosmoCashier = $this->user();
        $product = $this->product();
        $saborItem = $this->stock($product, $this->saborShelf1, 10);
        $cosmoItem = $this->stock($product, $this->cosmoShelf, 10);
        $saborShift = $this->openShift($saborCashier, $this->saborShop);
        $cosmoShift = $this->openShift($cosmoCashier, $this->cosmoShop);

        $this->sell($saborCashier, $product, 2)->assertSessionHasNoErrors();

        $this->assertEquals(8, (float) $saborItem->fresh()->quantity);
        $this->assertEquals(10, (float) $cosmoItem->fresh()->quantity);
        $order = DB::table('orders')->latest('id')->first();
        $this->assertEquals($this->saborShop->id, $order->sales_location_id);
        $this->assertEquals($saborShift->id, $order->cash_shift_id);

        $this->sell($cosmoCashier, $product, 3)->assertSessionHasNoErrors();

        $this->assertEquals(8, (float) $saborItem->fresh()->quantity);
        $this->assertEquals(7, (float) $cosmoItem->fresh()->quantity);
        $this->assertEquals($cosmoShift->id, DB::table('orders')->latest('id')->value('cash_shift_id'));
    }

    public function test_pos_screen_shows_stock_of_the_shop_and_warns_without_warehouse(): void
    {
        $cashier = $this->user();
        $product = $this->product(['name' => 'Solo en Cosmonauta']);
        $this->stock($product, $this->cosmoShelf, 10);
        $this->openShift($cashier, $this->saborShop);

        $this->actingAs($cashier)->get('/pos')->assertOk()
            ->assertSee('Sin stock en este depósito')
            ->assertDontSee('no tiene depósito asignado');

        $other = $this->user();
        $this->openShift($other, SalesLocation::create(['name' => 'Sin depósito', 'is_active' => true]));

        $this->actingAs($other)->get('/pos')->assertOk()->assertSee('no tiene depósito asignado');
    }

    public function test_cashier_only_closes_their_own_shift(): void
    {
        $saborCashier = $this->user();
        $saborShift = $this->openShift($saborCashier, $this->saborShop);
        $cosmoShift = $this->openShift($this->user(), $this->cosmoShop);

        $this->actingAs($saborCashier)->post('/cash-shifts/close', ['closing_amount' => 5]);

        $this->assertFalse((bool) $saborShift->fresh()->is_active);
        $this->assertTrue((bool) $cosmoShift->fresh()->is_active);
    }

    public function test_sale_requires_own_open_shift(): void
    {
        $product = $this->product();
        $this->openShift($this->user(), $this->saborShop);

        $this->sell($this->user(), $product, 1)->assertSessionHas('error');

        $this->assertEquals(0, DB::table('orders')->count());
    }

    public function test_user_cannot_open_two_shifts(): void
    {
        $cashier = $this->user();
        $this->openShift($cashier, $this->saborShop);

        $this->actingAs($cashier)->post('/cash-shifts/open', [
            'sales_location_id' => $this->cosmoShop->id,
            'opening_amount' => 0,
        ])->assertSessionHas('error');

        $this->assertEquals(1, CashShift::where('user_id', $cashier->id)->count());
    }

    public function test_sale_is_rejected_when_shop_warehouse_lacks_stock(): void
    {
        $cashier = $this->user();
        $product = $this->product(['is_stock_tracked' => true]);
        $elsewhere = $this->stock($product, $this->cosmoShelf, 10);
        $this->openShift($cashier, $this->saborShop);

        $this->sell($cashier, $product, 2)->assertSessionHasErrors('items');

        $this->assertEquals(10, (float) $elsewhere->fresh()->quantity);
        $this->assertEquals(0, DB::table('orders')->count());
        $this->assertEquals(0, StockMovement::count());
    }

    public function test_product_without_stock_control_sells_freely(): void
    {
        $cashier = $this->user();
        $service = $this->product(['name' => 'Servicio']);
        $this->openShift($cashier, $this->saborShop);

        $this->sell($cashier, $service, 2)->assertSessionHasNoErrors();

        $this->assertEquals(1, DB::table('orders')->count());
    }

    public function test_sale_takes_stock_across_shelves_and_kardex_matches(): void
    {
        $cashier = $this->user();
        $product = $this->product();
        $shelf1 = $this->stock($product, $this->saborShelf1, 3);
        $shelf2 = $this->stock($product, $this->saborShelf2, 3);
        $this->openShift($cashier, $this->saborShop);

        $this->sell($cashier, $product, 5, ['warehouse_id' => $this->sabor->id, 'location_id' => $this->saborShelf2->id])
            ->assertSessionHasNoErrors();

        $this->assertEquals(1, (float) $shelf1->fresh()->quantity);
        $this->assertEquals(0, (float) $shelf2->fresh()->quantity);
        $this->assertEquals(1, (float) $product->fresh()->stock_quantity);
        $this->assertEquals(-5, (float) StockMovement::where('product_id', $product->id)->sum('quantity'));
        $this->assertEquals($this->saborShelf2->id, OrderItem::where('product_id', $product->id)->value('location_id'));
    }

    public function test_recipe_consumes_ingredients_from_shop_warehouse(): void
    {
        $cashier = $this->user();
        $ingredient = $this->product(['name' => 'Arroz', 'is_stock_tracked' => true, 'is_raw_material' => true]);
        $cosmoItem = $this->stock($ingredient, $this->cosmoShelf, 10);
        $saborItem = $this->stock($ingredient, $this->saborShelf1, 10);
        $dish = $this->product(['name' => 'Plato', 'is_prepared' => true, 'is_stock_tracked' => true]);
        $recipe = Recipe::create(['product_id' => $dish->id, 'yield_quantity' => 1]);
        RecipeItem::create(['recipe_id' => $recipe->id, 'component_product_id' => $ingredient->id, 'quantity' => 1, 'wastage_percent' => 0]);
        $this->openShift($cashier, $this->saborShop);

        $this->sell($cashier, $dish, 2)->assertSessionHasNoErrors();

        $this->assertEquals(8, (float) $saborItem->fresh()->quantity);
        $this->assertEquals(10, (float) $cosmoItem->fresh()->quantity);
        $this->assertEquals($this->sabor->id, StockMovement::where('reason', 'sale_recipe')->value('warehouse_id'));
    }

    public function test_purchase_enters_the_chosen_warehouse(): void
    {
        $product = $this->product(['is_stock_tracked' => true]);
        $cosmoItem = $this->stock($product, $this->cosmoShelf, 1);
        $invoice = new SupplierInvoice();
        $invoice->id = 1;

        InventoryService::recordPurchaseEntry($product, 5, 4, $invoice, null, warehouseId: $this->sabor->id);

        $this->assertEquals(1, (float) $cosmoItem->fresh()->quantity);
        $this->assertEquals(5, (float) StockItem::where('product_id', $product->id)->where('warehouse_id', $this->sabor->id)->value('quantity'));
        $this->assertEquals(6, (float) $product->fresh()->stock_quantity);
        $this->assertEquals($this->sabor->id, StockMovement::where('reason', 'purchase')->value('warehouse_id'));
    }

    public function test_purchase_form_posts_warehouse(): void
    {
        $admin = $this->user('admin');
        $product = $this->product(['is_stock_tracked' => true]);
        $party = \App\Models\Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);

        $this->actingAs($admin)->get('/supplier-invoices/create')->assertOk()->assertSee('Depósito que recibe la mercancía');

        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'currency' => 'USD',
            'doc_type' => 'FC',
            'status' => 'pendiente',
            'warehouse_id' => $this->cosmo->id,
            'items' => [['product_id' => $product->id, 'quantity' => 4, 'unit_cost' => 2]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(4, (float) StockItem::where('product_id', $product->id)->where('warehouse_id', $this->cosmo->id)->value('quantity'));
    }

    private function createInvoice(Product $product, float $qty, float $cost = 2, ?Warehouse $warehouse = null): SupplierInvoice
    {
        $party = \App\Models\Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $this->supplier = Supplier::create(['party_id' => $party->id]);

        $this->actingAs($this->user('admin'))->post('/supplier-invoices', $this->invoicePayload($product, $qty, $cost, $warehouse))
            ->assertSessionHasNoErrors();

        return SupplierInvoice::latest('id')->firstOrFail();
    }

    private function invoicePayload(Product $product, float $qty, float $cost = 2, ?Warehouse $warehouse = null): array
    {
        return [
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'currency' => 'USD',
            'doc_type' => 'FC',
            'status' => 'pendiente',
            'warehouse_id' => ($warehouse ?? $this->sabor)->id,
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'unit_cost' => $cost]],
        ];
    }

    private function editInvoice(SupplierInvoice $invoice, array $payload)
    {
        return $this->actingAs($this->user('admin'))->from('/supplier-invoices')->put("/supplier-invoices/{$invoice->id}", $payload);
    }

    public function test_editing_invoice_without_changes_does_not_add_stock_again(): void
    {
        $product = $this->product(['is_stock_tracked' => true]);
        $invoice = $this->createInvoice($product, 10);

        $this->editInvoice($invoice, $this->invoicePayload($product, 10))->assertSessionHasNoErrors();
        $this->editInvoice($invoice, $this->invoicePayload($product, 10))->assertSessionHasNoErrors();

        $this->assertEquals(10, (float) $product->fresh()->stock_quantity);
        $this->assertEquals(2, (float) StockItem::where('product_id', $product->id)->value('average_cost'));
        $this->assertEquals(10, (float) StockMovement::where('product_id', $product->id)->sum('quantity'));
    }

    public function test_editing_invoice_applies_only_the_difference(): void
    {
        $product = $this->product(['is_stock_tracked' => true]);
        $invoice = $this->createInvoice($product, 10);

        $this->editInvoice($invoice, $this->invoicePayload($product, 12))->assertSessionHasNoErrors();
        $this->assertEquals(12, (float) $product->fresh()->stock_quantity);

        $this->editInvoice($invoice, $this->invoicePayload($product, 7))->assertSessionHasNoErrors();
        $this->assertEquals(7, (float) $product->fresh()->stock_quantity);
    }

    public function test_replacing_invoice_product_removes_the_old_entry(): void
    {
        $first = $this->product(['name' => 'Primero', 'is_stock_tracked' => true]);
        $second = $this->product(['name' => 'Segundo', 'is_stock_tracked' => true]);
        $invoice = $this->createInvoice($first, 10);

        $this->editInvoice($invoice, $this->invoicePayload($second, 4))->assertSessionHasNoErrors();

        $this->assertEquals(0, (float) $first->fresh()->stock_quantity);
        $this->assertEquals(4, (float) $second->fresh()->stock_quantity);
    }

    public function test_editing_invoice_respects_units_already_sold(): void
    {
        $cashier = $this->user();
        $product = $this->product(['is_stock_tracked' => true]);
        $invoice = $this->createInvoice($product, 10);
        $this->openShift($cashier, $this->saborShop);
        $this->sell($cashier, $product, 8)->assertSessionHasNoErrors();

        $this->editInvoice($invoice, $this->invoicePayload($product, 12))->assertSessionHasNoErrors();
        $this->assertEquals(4, (float) $product->fresh()->stock_quantity);
    }

    public function test_editing_invoice_below_sold_units_is_rejected_without_changes(): void
    {
        $cashier = $this->user();
        $product = $this->product(['is_stock_tracked' => true]);
        $invoice = $this->createInvoice($product, 10);
        $this->openShift($cashier, $this->saborShop);
        $this->sell($cashier, $product, 8)->assertSessionHasNoErrors();
        $movements = StockMovement::count();

        $this->editInvoice($invoice, $this->invoicePayload($product, 1))->assertSessionHasErrors('items');

        $this->assertEquals(2, (float) StockItem::where('product_id', $product->id)->value('quantity'));
        $this->assertEquals($movements, StockMovement::count());
        $this->assertEquals(1, $invoice->items()->count());
    }

    public function test_edit_form_keeps_the_warehouse_of_the_original_entry(): void
    {
        $product = $this->product(['is_stock_tracked' => true]);
        $invoice = $this->createInvoice($product, 5, 2, $this->cosmo);

        $this->actingAs($this->user('admin'))->get("/supplier-invoices/{$invoice->id}/edit")->assertOk()
            ->assertSee('<option value="' . $this->cosmo->id . '" selected', false);
    }

    public function test_transfer_cannot_exceed_origin_stock(): void
    {
        $user = $this->user('almacenista');
        $product = $this->product();
        $origin = $this->stock($product, $this->saborShelf1, 3);

        $this->actingAs($user)->post('/stock/adjust', [
            'product_id' => $product->id, 'type' => 'transfer', 'reason' => 'traslado', 'quantity' => 10,
            'warehouse_id' => $this->sabor->id, 'location_id' => $this->saborShelf1->id,
            'to_warehouse_id' => $this->cosmo->id, 'to_location_id' => $this->cosmoShelf->id,
        ])->assertSessionHasErrors('quantity');

        $this->assertEquals(3, (float) $origin->fresh()->quantity);
        $this->assertEquals(1, StockItem::where('product_id', $product->id)->count());

        $this->actingAs($user)->post('/stock/adjust', [
            'product_id' => $product->id, 'type' => 'transfer', 'reason' => 'traslado', 'quantity' => 2,
            'warehouse_id' => $this->sabor->id, 'location_id' => $this->saborShelf1->id,
            'to_warehouse_id' => $this->cosmo->id, 'to_location_id' => $this->cosmoShelf->id,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(1, (float) $origin->fresh()->quantity);
        $this->assertEquals(2, (float) StockItem::where('location_id', $this->cosmoShelf->id)->value('quantity'));
        $this->assertEquals(3, (float) $product->fresh()->stock_quantity);
    }

    public function test_transfer_rejects_location_of_another_warehouse(): void
    {
        $user = $this->user('almacenista');
        $product = $this->product();
        $this->stock($product, $this->saborShelf1, 3);

        $this->actingAs($user)->post('/stock/adjust', [
            'product_id' => $product->id, 'type' => 'transfer', 'reason' => 'traslado', 'quantity' => 1,
            'warehouse_id' => $this->sabor->id, 'location_id' => $this->saborShelf1->id,
            'to_warehouse_id' => $this->cosmo->id, 'to_location_id' => $this->saborShelf2->id,
        ])->assertSessionHasErrors('to_location_id');
    }

    public function test_setting_lower_total_spreads_across_locations(): void
    {
        $product = $this->product();
        $shelf1 = $this->stock($product, $this->saborShelf1, 3);
        $shelf2 = $this->stock($product, $this->saborShelf2, 3);

        InventoryService::setProductQuantity($product, 1, 'variant_edit');

        $this->assertEquals(1, (float) $shelf1->fresh()->quantity + (float) $shelf2->fresh()->quantity);
        $this->assertEquals(1, (float) $product->fresh()->stock_quantity);
    }

    public function test_seeders_create_a_single_main_warehouse(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(1, Warehouse::where('name', 'Almacén principal')->count());
        $this->assertEquals('MAIN', Warehouse::where('name', 'Almacén principal')->value('code'));
    }

    public function test_migration_unifies_principal_into_main_keeping_quantities(): void
    {
        $main = Warehouse::create(['code' => 'MAIN', 'name' => 'Almacén principal', 'is_active' => true]);
        $principal = Warehouse::create(['code' => 'PRINCIPAL', 'name' => 'Almacén principal', 'is_active' => true]);
        $gen = Location::create(['warehouse_id' => $main->id, 'code' => 'GEN', 'name' => 'General', 'is_active' => true]);
        $general = Location::create(['warehouse_id' => $principal->id, 'code' => 'GENERAL', 'name' => 'General', 'is_active' => true]);
        $aisle = Location::create(['warehouse_id' => $principal->id, 'code' => 'A-1', 'name' => 'Pasillo', 'is_active' => true]);

        $merged = $this->product(['name' => 'Unido']);
        StockItem::create(['product_id' => $merged->id, 'warehouse_id' => $main->id, 'location_id' => $gen->id, 'quantity' => 4, 'average_cost' => 1]);
        StockItem::create(['product_id' => $merged->id, 'warehouse_id' => $principal->id, 'location_id' => $general->id, 'quantity' => 4, 'average_cost' => 3]);
        $moved = $this->product(['name' => 'Movido']);
        $movedItem = StockItem::create(['product_id' => $moved->id, 'warehouse_id' => $principal->id, 'location_id' => $aisle->id, 'quantity' => 7]);
        $orphan = $this->product(['name' => 'Sin depósito']);
        $orphanItem = StockItem::create(['product_id' => $orphan->id, 'quantity' => 2]);
        $untouched = $this->stock($this->product(['name' => 'Otro']), $this->saborShelf1, 9);
        $shop = SalesLocation::create(['name' => 'Central', 'warehouse_id' => $principal->id, 'is_active' => true]);

        $migration = require database_path('migrations/2026_10_30_000000_unify_main_warehouse.php');
        $migration->up();

        $this->assertNull(Warehouse::where('code', 'PRINCIPAL')->first());
        $mergedRow = StockItem::where('product_id', $merged->id)->sole();
        $this->assertEquals(8, (float) $mergedRow->quantity);
        $this->assertEquals(2, (float) $mergedRow->average_cost);
        $this->assertEquals($gen->id, $mergedRow->location_id);
        $this->assertEquals($main->id, $movedItem->fresh()->warehouse_id);
        $this->assertEquals($main->id, $aisle->fresh()->warehouse_id);
        $this->assertEquals($main->id, $orphanItem->fresh()->warehouse_id);
        $this->assertEquals(2, (float) $orphanItem->fresh()->quantity);
        $this->assertEquals($this->sabor->id, $untouched->fresh()->warehouse_id);
        $this->assertEquals($main->id, $shop->fresh()->warehouse_id);
    }
}
