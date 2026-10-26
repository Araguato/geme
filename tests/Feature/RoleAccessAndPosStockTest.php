<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessAndPosStockTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRoles(array $roles): User
    {
        $user = User::factory()->create();
        foreach ($roles as $role) {
            $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);
        }

        return $user;
    }

    public function test_sales_roles_cannot_manage_products_or_stock(): void
    {
        $user = $this->userWithRoles(['vendedor', 'cajero', 'despachador']);

        $this->actingAs($user)->get('/products')->assertForbidden();
        $this->actingAs($user)->get('/products/create')->assertForbidden();
        $this->actingAs($user)->get('/stock/adjust')->assertForbidden();
        $this->actingAs($user)->post('/stock/adjust', [])->assertForbidden();
        $this->actingAs($user)->get('/warehouses')->assertForbidden();
        $this->actingAs($user)->get('/backup/database')->assertForbidden();
        $this->actingAs($user)->get('/employees')->assertForbidden();

        $this->actingAs($user)->get('/pos')->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('id="dashboardProducts"', false);
    }

    public function test_customers_cannot_access_back_office(): void
    {
        $user = $this->userWithRoles(['cliente']);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/pos')->assertForbidden();
        $this->actingAs($user)->get('/products')->assertForbidden();
    }

    public function test_inventory_roles_can_manage_products(): void
    {
        $user = $this->userWithRoles(['almacenista']);

        $this->actingAs($user)->get('/products')->assertOk();
        $this->actingAs($user)->get('/stock')->assertOk();
        $this->actingAs($user)->get('/pos')->assertForbidden();
    }

    public function test_debug_env_route_is_gone(): void
    {
        $this->get('/debug-env')->assertNotFound();
    }

    public function test_product_can_be_saved_without_image(): void
    {
        $user = $this->userWithRoles(['admin']);
        $category = Category::create(['name' => 'General', 'is_active' => true]);

        $response = $this->actingAs($user)->post('/products', [
            'category_id' => $category->id,
            'name' => 'Producto sin foto',
            'price' => 10,
            'image' => null,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Producto sin foto']);
    }

    public function test_pos_sale_discounts_stock_and_records_movement(): void
    {
        $user = $this->userWithRoles(['cajero']);
        $category = Category::create(['name' => 'General', 'is_active' => true]);
        $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Principal', 'is_active' => true]);
        $location = Location::create(['warehouse_id' => $warehouse->id, 'code' => 'GEN', 'name' => 'General', 'is_active' => true]);

        // Producto con existencias pero sin marcar "Controla inventario"
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cable USB',
            'price' => 5,
            'is_active' => true,
        ]);
        StockItem::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'location_id' => $location->id,
            'quantity' => 10,
            'average_cost' => 2,
            'min_quantity' => 0,
        ]);
        CashShift::create(['user_id' => $user->id, 'opened_at' => now(), 'opening_amount' => 0, 'is_active' => true]);

        $this->actingAs($user)->post('/pos', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 5],
            ],
            'payment' => ['method' => 'cash', 'amount' => 15],
        ])->assertSessionHasNoErrors()->assertRedirect(route('pos.index'));

        $this->assertEquals(7, (float) StockItem::where('product_id', $product->id)->value('quantity'));
        $this->assertTrue(
            StockMovement::where('product_id', $product->id)
                ->where('reference_type', 'order')
                ->where('quantity', -3)
                ->exists()
        );
    }
}
