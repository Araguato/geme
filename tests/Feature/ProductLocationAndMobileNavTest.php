<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLocationAndMobileNavTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

        return $user;
    }

    public function test_new_product_form_proposes_main_warehouse_location(): void
    {
        $main = Warehouse::firstOrCreate(['code' => 'MAIN'], ['name' => 'Almacén principal', 'is_active' => true]);
        $general = Location::firstOrCreate(['warehouse_id' => $main->id, 'code' => 'GEN'], ['name' => 'General', 'is_active' => true]);
        $other = Warehouse::create(['code' => 'B2', 'name' => 'Depósito 2', 'is_active' => true]);
        Location::create(['warehouse_id' => $other->id, 'code' => 'X', 'name' => 'Estante X', 'is_active' => true]);

        $response = $this->actingAs($this->user('almacenista'))->get('/products/create')->assertOk();

        $this->assertSame($main->id, $response->viewData('defaultWarehouseId'));
        $this->assertSame($general->id, $response->viewData('defaultLocationId'));
        $response->assertSee('value="' . $general->id . '"', false)
            ->assertSee('Producto sin ubicación')
            ->assertSee('Guardar de todos modos');
    }

    public function test_product_without_location_can_still_be_saved(): void
    {
        $category = Category::create(['name' => 'Varios', 'is_active' => true]);

        $this->actingAs($this->user('admin'))->post('/products', [
            'category_id' => $category->id,
            'name' => 'Sin sitio',
            'price' => 5,
            'location_id' => '',
        ])->assertRedirect('/products');

        $this->assertDatabaseHas('products', ['name' => 'Sin sitio', 'location_id' => null]);
    }

    public function test_products_list_filters_and_flags_products_without_location(): void
    {
        $main = Warehouse::firstOrCreate(['code' => 'MAIN'], ['name' => 'Almacén principal', 'is_active' => true]);
        $loc = Location::firstOrCreate(['warehouse_id' => $main->id, 'code' => 'GEN'], ['name' => 'General', 'is_active' => true]);
        $category = Category::create(['name' => 'Varios', 'is_active' => true]);
        Product::create(['category_id' => $category->id, 'name' => 'Con sitio', 'price' => 1, 'is_active' => true, 'warehouse_id' => $main->id, 'location_id' => $loc->id]);
        Product::create(['category_id' => $category->id, 'name' => 'Perdido', 'price' => 1, 'is_active' => true]);
        Product::create(['category_id' => $category->id, 'name' => 'Instalación', 'price' => 1, 'is_active' => true, 'is_service' => true]);

        $user = $this->user('almacenista');

        $all = $this->actingAs($user)->get('/products')->assertOk()->assertSee('Sin ubicación');
        $this->assertSame(1, $all->viewData('missingLocationCount'));

        $names = $this->actingAs($user)->get('/products?sin_ubicacion=1')->viewData('products')->pluck('name')->all();
        $this->assertSame(['Perdido'], $names);
    }

    public function test_layout_has_visible_mobile_menu_and_home_link(): void
    {
        $this->actingAs($this->user('almacenista'))->get('/stock')
            ->assertOk()
            ->assertSee('navbar-toggler', false)
            ->assertSee('navbar-brand-home', false)
            ->assertSee(route('home'), false);
    }

    /** El formulario envía la sección de variantes vacía aunque esté oculta. */
    public function test_product_form_with_empty_hidden_variant_rows_saves(): void
    {
        $category = Category::create(['name' => 'Varios', 'is_active' => true]);
        $admin = $this->user('admin');

        $this->actingAs($admin)->post('/products', [
            'category_id' => $category->id,
            'name' => 'Simple',
            'price' => 2,
            'variant_attributes' => [['name' => '', 'values' => '']],
        ])->assertSessionHasNoErrors()->assertRedirect('/products');
        $this->assertDatabaseHas('products', ['name' => 'Simple']);

        // Con "tiene variantes" marcado, los atributos siguen siendo obligatorios
        $this->actingAs($admin)->post('/products', [
            'category_id' => $category->id,
            'name' => 'Con variantes',
            'price' => 2,
            'has_variants' => '1',
            'variant_attributes' => [['name' => '', 'values' => '']],
        ])->assertSessionHasErrors(['variant_attributes.0.name', 'variant_attributes.0.values']);
    }
}
