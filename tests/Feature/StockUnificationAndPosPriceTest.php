<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Category;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesLocation;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockUnificationAndPosPriceTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'General', 'is_active' => true]);
    }

    private function userWithRoles(array $roles): User
    {
        $user = User::factory()->create();
        foreach ($roles as $role) {
            $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);
        }

        return $user;
    }

    private function product(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => 'Funda',
            'price' => 10,
            'is_active' => true,
        ], $attrs));
    }

    private function openShift(User $user, ?SalesLocation $location = null): void
    {
        CashShift::create([
            'user_id' => $user->id,
            'sales_location_id' => $location?->id,
            'opened_at' => now(),
            'opening_amount' => 0,
            'is_active' => true,
        ]);
    }

    private function sell(User $user, Product $product, float $qty, float $price, array $extra = [])
    {
        return $this->actingAs($user)->post('/pos', [
            'items' => [array_merge(['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => $price], $extra)],
            'payment' => ['method' => 'cash', 'amount' => $qty * $price],
        ]);
    }

    public function test_variant_stock_from_form_is_sold_from_inventory(): void
    {
        $admin = $this->userWithRoles(['admin']);

        $this->actingAs($admin)->post('/products', [
            'category_id' => $this->category->id,
            'name' => 'Cable',
            'price' => 5,
            'has_variants' => '1',
            'variant_attributes' => [['name' => 'Color', 'values' => 'Negro']],
            'variants' => [['sku' => 'CAB-N', 'price' => 5, 'stock_quantity' => 8, 'variant_attributes' => json_encode(['Color' => 'Negro'])]],
        ])->assertSessionHasNoErrors();

        $variant = Product::where('sku', 'CAB-N')->firstOrFail();
        $this->assertEquals(8, (float) StockItem::where('product_id', $variant->id)->sum('quantity'));

        $this->openShift($admin);
        $this->sell($admin, $variant, 3, 5)->assertSessionHasNoErrors();

        $this->assertEquals(5, (float) StockItem::where('product_id', $variant->id)->sum('quantity'));
        $this->assertEquals(5, (float) $variant->fresh()->stock_quantity);
    }

    public function test_cashier_cannot_change_price_in_pos(): void
    {
        $cashier = $this->userWithRoles(['cajero']);
        $product = $this->product(['price' => 10]);
        $this->openShift($cashier);

        $this->sell($cashier, $product, 1, 1)->assertSessionHasNoErrors();

        $this->assertEquals(10, (float) OrderItem::where('product_id', $product->id)->value('unit_price'));
    }

    public function test_price_change_allowed_product_and_supervisor_keep_sent_price(): void
    {
        $cashier = $this->userWithRoles(['cajero']);
        $flexible = $this->product(['name' => 'Servicio técnico', 'price' => 10, 'is_price_change_allowed' => true]);
        $this->openShift($cashier);
        $this->sell($cashier, $flexible, 1, 25)->assertSessionHasNoErrors();
        $this->assertEquals(25, (float) OrderItem::where('product_id', $flexible->id)->value('unit_price'));

        $supervisor = $this->userWithRoles(['supervisor']);
        $fixed = $this->product(['name' => 'Cargador', 'price' => 10]);
        $this->openShift($supervisor);
        $this->sell($supervisor, $fixed, 1, 8)->assertSessionHasNoErrors();
        $this->assertEquals(8, (float) OrderItem::where('product_id', $fixed->id)->value('unit_price'));
    }

    public function test_pos_only_discounts_stock_of_the_shop_warehouse(): void
    {
        $cashier = $this->userWithRoles(['cajero']);
        $shopA = Warehouse::create(['code' => 'A', 'name' => 'Tienda A', 'is_active' => true]);
        $shopB = Warehouse::create(['code' => 'B', 'name' => 'Tienda B', 'is_active' => true]);
        $locA = Location::create(['warehouse_id' => $shopA->id, 'code' => 'GEN', 'name' => 'General', 'is_active' => true]);
        $locB = Location::create(['warehouse_id' => $shopB->id, 'code' => 'GEN', 'name' => 'General', 'is_active' => true]);
        $product = $this->product();
        StockItem::create(['product_id' => $product->id, 'warehouse_id' => $shopA->id, 'location_id' => $locA->id, 'quantity' => 2]);
        StockItem::create(['product_id' => $product->id, 'warehouse_id' => $shopB->id, 'location_id' => $locB->id, 'quantity' => 20]);

        $salesLocation = SalesLocation::create(['name' => 'Tienda A', 'warehouse_id' => $shopA->id, 'is_active' => true]);
        $this->openShift($cashier, $salesLocation);

        // Aunque el formulario mande la ubicación de la tienda B, se descuenta de la A
        $this->sell($cashier, $product, 1, 10, ['warehouse_id' => $shopB->id, 'location_id' => $locB->id])
            ->assertSessionHasNoErrors();

        $this->assertEquals(1, (float) StockItem::where('location_id', $locA->id)->value('quantity'));
        $this->assertEquals(20, (float) StockItem::where('location_id', $locB->id)->value('quantity'));
        $this->assertEquals(21, (float) $product->fresh()->stock_quantity);
    }

    public function test_migration_moves_legacy_stock_quantity_into_inventory(): void
    {
        $legacy = $this->product(['name' => 'Audífonos', 'stock_quantity' => 6, 'cost' => 4]);
        $tracked = $this->product(['name' => 'Mouse', 'stock_quantity' => 99]);
        StockItem::create(['product_id' => $tracked->id, 'quantity' => 3]);

        $migration = require database_path('migrations/2026_10_27_000000_move_product_stock_quantity_to_stock_items.php');
        $migration->up();

        $item = StockItem::where('product_id', $legacy->id)->firstOrFail();
        $this->assertEquals(6, (float) $item->quantity);
        $this->assertEquals('MAIN', Warehouse::find($item->warehouse_id)->code);
        $this->assertEquals(3, (float) $tracked->fresh()->stock_quantity);
        $this->assertEquals(1, StockItem::where('product_id', $tracked->id)->count());
    }

    public function test_stock_adjustment_updates_product_stock_quantity(): void
    {
        $user = $this->userWithRoles(['almacenista']);
        $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Principal', 'is_active' => true]);
        $location = Location::create(['warehouse_id' => $warehouse->id, 'code' => 'GEN', 'name' => 'General', 'is_active' => true]);
        $product = $this->product();

        $this->actingAs($user)->post('/stock/adjust', [
            'product_id' => $product->id,
            'type' => 'in',
            'reason' => 'compra',
            'warehouse_id' => $warehouse->id,
            'location_id' => $location->id,
            'quantity' => 12,
            'unit_cost' => 3,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(12, (float) $product->fresh()->stock_quantity);
    }
}
