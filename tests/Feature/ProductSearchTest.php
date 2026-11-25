<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_and_search_match_every_word_in_any_order_and_include_variants(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);
        $cat = Category::create(['name' => 'General', 'is_active' => true]);
        $parent = Product::create(['category_id' => $cat->id, 'name' => 'Salsa de tomate', 'price' => 2, 'is_active' => true]);
        Product::create(['category_id' => $cat->id, 'name' => 'Salsa de tomate grande', 'price' => 3, 'is_active' => true, 'parent_product_id' => $parent->id]);
        Product::create(['category_id' => $cat->id, 'name' => 'Aceite', 'price' => 5, 'is_active' => true]);

        $this->actingAs($user)->get('/products?search=tomate salsa')
            ->assertOk()->assertSee('Salsa de tomate grande')->assertDontSee('Aceite');
        $this->actingAs($user)->get('/products/search?search=tomate+salsa')
            ->assertOk()->assertSee('Salsa de tomate grande')->assertDontSee('Aceite');
        $this->actingAs($user)->get('/products/search')->assertOk();
        $this->actingAs($user)->getJson('/products/api-search?q=aceite')->assertOk()->assertJsonCount(1, 'products');
    }
}
