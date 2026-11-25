<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Party;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Role;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierInvoiceProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'General', 'is_active' => true]);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

        return $user;
    }

    private function product(string $name, array $attrs = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $this->category->id, 'name' => $name, 'price' => 10, 'is_active' => true,
        ], $attrs));
    }

    public function test_search_finds_by_name_sku_and_barcode_and_skips_inactive(): void
    {
        $salsa = $this->product('Salsa de tomate', ['sku' => 'SAL-01', 'cost' => 1.5, 'default_unit' => 'und']);
        ProductBarcode::create(['product_id' => $salsa->id, 'barcode' => '7591234567890', 'multiplier' => 1]);
        $this->product('Mayonesa', ['sku' => 'MAY-01']);
        $this->product('Salsa vieja', ['is_active' => false]);

        $admin = $this->user('admin');

        $byName = $this->actingAs($admin)->getJson(route('supplier-invoices.products.search', ['q' => 'salsa']))->assertOk();
        $this->assertSame(['Salsa de tomate'], collect($byName->json('products'))->pluck('name')->all());
        $this->assertEquals(1.5, $byName->json('products.0.cost'));
        $this->assertSame('und', $byName->json('products.0.default_unit'));

        $bySku = $this->actingAs($admin)->getJson(route('supplier-invoices.products.search', ['q' => 'MAY-0']))->assertOk();
        $this->assertSame(['Mayonesa'], collect($bySku->json('products'))->pluck('name')->all());

        $byBarcode = $this->actingAs($admin)->getJson(route('supplier-invoices.products.search', ['q' => '759123']))->assertOk();
        $this->assertSame(['Salsa de tomate'], collect($byBarcode->json('products'))->pluck('name')->all());

        // El comodín % se busca como texto, no como patrón
        $wild = $this->actingAs($admin)->getJson(route('supplier-invoices.products.search', ['q' => '%']))->assertOk();
        $this->assertSame([], $wild->json('products'));

        // Sin texto devuelve los primeros productos activos
        $all = $this->actingAs($admin)->getJson(route('supplier-invoices.products.search'))->assertOk();
        $this->assertCount(2, $all->json('products'));
    }

    public function test_search_reaches_products_beyond_the_first_25(): void
    {
        foreach (range(1, 40) as $i) {
            $this->product(sprintf('Producto %02d', $i));
        }
        $this->product('Zapallo especial');

        $res = $this->actingAs($this->user('admin'))->getJson(route('supplier-invoices.products.search', ['q' => 'zapallo']))->assertOk();
        $this->assertSame(['Zapallo especial'], collect($res->json('products'))->pluck('name')->all());

        $this->actingAs($this->user('admin'))->get('/supplier-invoices/create')->assertOk()
            ->assertSee('product-search', false)
            ->assertSee('Crear producto nuevo')
            ->assertDontSee('Producto 30');
    }

    public function test_only_finance_roles_can_search_and_create(): void
    {
        $this->actingAs($this->user('contable'))->getJson(route('supplier-invoices.products.search'))->assertOk();
        $this->actingAs($this->user('supervisor'))->getJson(route('supplier-invoices.products.search'))->assertOk();

        $cashier = $this->user('cajero');
        $this->actingAs($cashier)->getJson(route('supplier-invoices.products.search'))->assertForbidden();
        $this->actingAs($cashier)->postJson(route('supplier-invoices.products.store'), ['name' => 'X', 'category_id' => $this->category->id])->assertForbidden();
    }

    public function test_quick_create_makes_a_stock_tracked_product_and_audits_it(): void
    {
        $contable = $this->user('contable');

        $res = $this->actingAs($contable)->postJson(route('supplier-invoices.products.store'), [
            'name' => '  Harina PAN 1kg ', 'category_id' => $this->category->id, 'sku' => 'HAR-01',
            'default_unit' => 'und', 'cost' => 1.2, 'tax_rate' => 16,
        ])->assertCreated();

        $product = Product::findOrFail($res->json('product.id'));
        $this->assertSame('Harina PAN 1kg', $product->name);
        $this->assertTrue($product->is_active);
        $this->assertTrue($product->is_stock_tracked);
        $this->assertEquals(1.2, (float) $product->cost);
        $this->assertEquals(1.2, (float) $product->price); // sin precio: usa el costo
        $this->assertTrue($res->json('price_from_cost'));
        $this->assertTrue(AuditLog::where('action', 'product_created_from_supplier_invoice')->where('entity_id', $product->id)->exists());

        // Un precio indicado se respeta
        $res2 = $this->actingAs($contable)->postJson(route('supplier-invoices.products.store'), [
            'name' => 'Aceite 1L', 'category_id' => $this->category->id, 'cost' => 2, 'price' => 3.5,
        ])->assertCreated();
        $this->assertEquals(3.5, (float) Product::find($res2->json('product.id'))->price);
        $this->assertFalse($res2->json('price_from_cost'));
    }

    public function test_quick_create_rejects_duplicates_and_bad_data(): void
    {
        $existing = $this->product('Azúcar 1kg', ['sku' => 'AZU-01']);
        $admin = $this->user('admin');

        $dup = $this->actingAs($admin)->postJson(route('supplier-invoices.products.store'), [
            'name' => 'azúcar 1KG', 'category_id' => $this->category->id,
        ])->assertStatus(422);
        $this->assertSame($existing->id, $dup->json('existing.id'));
        $this->assertSame(1, Product::count());

        $this->actingAs($admin)->postJson(route('supplier-invoices.products.store'), ['name' => 'Nuevo', 'category_id' => $this->category->id, 'sku' => 'AZU-01'])
            ->assertStatus(422)->assertJsonValidationErrors('sku');
        $this->actingAs($admin)->postJson(route('supplier-invoices.products.store'), ['name' => 'Nuevo'])
            ->assertStatus(422)->assertJsonValidationErrors('category_id');
        $this->actingAs($admin)->postJson(route('supplier-invoices.products.store'), ['category_id' => $this->category->id])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->assertSame(1, Product::count());
    }

    public function test_a_product_created_on_the_fly_enters_stock_with_the_invoice(): void
    {
        $admin = $this->user('admin');
        $party = Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);

        $id = $this->actingAs($admin)->postJson(route('supplier-invoices.products.store'), [
            'name' => 'Pasta larga', 'category_id' => $this->category->id, 'cost' => 1,
        ])->assertCreated()->json('product.id');

        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'USD', 'doc_type' => 'FC', 'status' => 'pendiente',
            'items' => [['product_id' => $id, 'quantity' => 12, 'unit_cost' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(12, (float) StockItem::where('product_id', $id)->sum('quantity'));
    }

    public function test_edit_form_shows_the_chosen_product(): void
    {
        $admin = $this->user('admin');
        $product = $this->product('Atún en lata', ['sku' => 'ATU-01', 'is_stock_tracked' => true]);
        $party = Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);

        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'USD', 'doc_type' => 'FC', 'status' => 'pendiente',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 2]],
        ])->assertSessionHasNoErrors();

        $invoice = SupplierInvoice::latest('id')->firstOrFail();
        $this->actingAs($admin)->get("/supplier-invoices/{$invoice->id}/edit")->assertOk()
            ->assertSee('Atún en lata (ATU-01)', false)
            ->assertSee('value="' . $product->id . '"', false);
    }

    public function test_blank_tax_fields_are_calculated_with_the_rate_in_percent(): void
    {
        $admin = $this->user('admin');
        $party = Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);
        $product = $this->product('Galletas', ['is_stock_tracked' => true]);

        // Como envía el formulario: campos vacíos para subtotal, impuesto y total
        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'USD', 'doc_type' => 'FC', 'status' => 'pendiente',
            'items' => [['product_id' => $product->id, 'quantity' => 12, 'unit_cost' => 2.5, 'tax_rate' => 16, 'tax_amount' => '', 'subtotal' => '', 'total' => '']],
        ])->assertSessionHasNoErrors();

        $invoice = SupplierInvoice::latest('id')->firstOrFail();
        $item = $invoice->items()->firstOrFail();
        $this->assertEquals(30.00, (float) $item->subtotal);
        $this->assertEquals(4.80, (float) $item->tax_amount);
        $this->assertEquals(34.80, (float) $item->total);
        $this->assertEquals(34.80, (float) $invoice->total_amount);
        $this->assertEquals(4.80, (float) $invoice->total_tax);

        // Un importe escrito a mano se respeta
        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'USD', 'doc_type' => 'FC', 'status' => 'pendiente',
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 10, 'tax_rate' => 16, 'tax_amount' => 1.5]],
        ])->assertSessionHasNoErrors();
        $this->assertEquals(1.5, (float) SupplierInvoice::latest('id')->first()->items()->first()->tax_amount);
    }

    public function test_form_has_live_totals_summary(): void
    {
        $this->actingAs($this->user('admin'))->get('/supplier-invoices/create')->assertOk()
            ->assertSee('id="sum-total"', false);
    }

    public function test_cost_is_required_and_must_be_positive(): void
    {
        $admin = $this->user('admin');
        $party = Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);
        $product = $this->product('Arroz', ['is_stock_tracked' => true]);

        $base = ['supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'USD', 'doc_type' => 'FC', 'status' => 'pendiente'];

        foreach (['', null, 0, '0'] as $cost) {
            $this->actingAs($admin)->post('/supplier-invoices', $base + [
                'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => $cost, 'tax_rate' => 16]],
            ])->assertSessionHasErrors('items.0.unit_cost');
        }

        $this->assertSame(0, SupplierInvoice::count());
        $this->assertEquals(0, (float) StockItem::where('product_id', $product->id)->sum('quantity'));
    }

    public function test_form_offers_unit_and_vat_menus_with_16_percent_by_default(): void
    {
        Unit::create(['code' => 'KG', 'name' => 'Kilogramo', 'category' => 'peso', 'is_active' => true]);
        Unit::create(['code' => 'UND', 'name' => 'Unidad', 'category' => 'pieza', 'is_active' => true]);

        $this->actingAs($this->user('admin'))->get('/supplier-invoices/create')->assertOk()
            ->assertSee('<select name="items[0][unit]"', false)
            ->assertSee('<option value="kg"', false)
            ->assertSee('<option value="unidad" selected>', false)
            ->assertSee('<select name="items[0][tax_rate]"', false)
            ->assertSee('<option value="16" selected>', false)
            ->assertSee('<option value="8"', false)
            ->assertSee('0 % · Exento');
    }

    public function test_edit_form_keeps_the_saved_vat_and_unit(): void
    {
        Unit::create(['code' => 'KG', 'name' => 'Kilogramo', 'category' => 'peso', 'is_active' => true]);
        $admin = $this->user('admin');
        $party = Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);
        $product = $this->product('Queso', ['is_stock_tracked' => true]);

        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'USD', 'doc_type' => 'FC', 'status' => 'pendiente',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit' => 'kg', 'unit_cost' => 5, 'tax_rate' => 8]],
        ])->assertSessionHasNoErrors();

        $invoice = SupplierInvoice::latest('id')->firstOrFail();
        $html = $this->actingAs($admin)->get("/supplier-invoices/{$invoice->id}/edit")->assertOk()->getContent();

        // Solo el menú del renglón (el de la ventana de producto nuevo propone 16 % aparte)
        preg_match('/<select name="items\[0\]\[tax_rate\]".*?<\/select>/s', $html, $tax);
        $this->assertStringContainsString('<option value="8" selected>', $tax[0]);
        $this->assertStringNotContainsString('<option value="16" selected>', $tax[0]);

        preg_match('/<select name="items\[0\]\[unit\]".*?<\/select>/s', $html, $unit);
        $this->assertStringContainsString('<option value="kg" selected>', $unit[0]);
    }

    public function test_search_suggests_a_cost_but_never_uses_the_sale_price_as_cost(): void
    {
        $admin = $this->user('admin');
        $party = Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);
        $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Principal', 'is_active' => true]);
        $location = Location::create(['warehouse_id' => $warehouse->id, 'code' => 'G', 'name' => 'General', 'is_active' => true]);

        // Importado: solo precio de venta, sin costo ni ubicación
        $onlyPrice = $this->product('Solo precio', ['price' => 12.5]);
        $withCost = $this->product('Con costo', ['price' => 9, 'cost' => 4]);
        $withStock = $this->product('Con stock', ['price' => 9, 'is_stock_tracked' => true]);
        StockItem::create(['product_id' => $withStock->id, 'warehouse_id' => $warehouse->id, 'location_id' => $location->id, 'quantity' => 10, 'average_cost' => 3.2]);
        $bought = $this->product('Ya comprado', ['price' => 9, 'cost' => 4, 'is_stock_tracked' => true]);

        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'USD', 'doc_type' => 'FC', 'status' => 'pendiente',
            'items' => [['product_id' => $bought->id, 'quantity' => 1, 'unit_cost' => 5.5, 'tax_rate' => 16]],
        ])->assertSessionHasNoErrors();

        $by = fn (string $q, string $currency = 'USD') => collect(
            $this->actingAs($admin)->getJson(route('supplier-invoices.products.search', ['q' => $q, 'currency' => $currency]))->assertOk()->json('products')
        )->first();

        $p = $by('Solo precio');
        $this->assertNull($p['suggested_cost']);          // el precio de venta no se usa como costo
        $this->assertEquals(12.5, $p['price']);           // pero se ofrece como referencia

        $p = $by('Con costo');
        $this->assertEquals(4.0, $p['suggested_cost']);
        $this->assertSame('costo del producto', $p['cost_source']);

        $p = $by('Con stock');
        $this->assertEquals(3.2, $p['suggested_cost']);
        $this->assertSame('costo promedio', $p['cost_source']);

        $p = $by('Ya comprado');
        $this->assertEquals(5.5, $p['suggested_cost']);   // la última compra manda
        $this->assertSame('última compra', $p['cost_source']);

        // En una factura en Bs no se sugieren costos en USD
        $this->assertNull($by('Con costo', 'VES')['suggested_cost']);
        $this->assertNull($by('Ya comprado', 'VES')['suggested_cost']);
    }

    public function test_invoice_in_bs_uses_the_emission_rate_and_not_todays_rate(): void
    {
        $admin = $this->user('admin');
        $party = Party::create(['name' => 'Mia Isabella', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);
        $colgate = $this->product('Colgate', ['is_stock_tracked' => true]);
        $papel = $this->product('Papel higiénico', ['is_stock_tracked' => true]);
        \App\Models\Setting::set('bcv_rate', 924.3534); // tasa de hoy (TPV): no debe cambiar

        // Factura real: renglones en Bs sin IVA, tasa de la fecha de emisión 917,7751
        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'VES', 'doc_type' => 'FC',
            'status' => 'pendiente', 'bcv_rate_at_issue' => '917.7751',
            'items' => [
                ['product_id' => $colgate->id, 'quantity' => 12, 'unit_cost' => '595.009167', 'tax_rate' => 16, 'tax_amount' => '', 'subtotal' => '', 'total' => ''],
                ['product_id' => $papel->id, 'quantity' => 12, 'unit_cost' => '1021.667500', 'tax_rate' => 16, 'tax_amount' => '', 'subtotal' => '', 'total' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $invoice = SupplierInvoice::latest('id')->firstOrFail();
        $this->assertEquals(19400.12, round((float) $invoice->total_items, 2));
        $this->assertEquals(3104.02, round((float) $invoice->total_tax, 2));
        $this->assertEquals(22504.14, round((float) $invoice->amount_bs, 2));
        $this->assertEquals(24.52, (float) $invoice->amount_usd);
        $this->assertEquals(917.7751, (float) $invoice->bcv_rate_at_issue);

        // El inventario se valora en USD con la tasa de emisión
        $this->assertEqualsWithDelta(0.648, (float) StockItem::where('product_id', $colgate->id)->value('average_cost'), 0.001);
        // La tasa global del TPV no se tocó
        $this->assertEquals(924.3534, (float) \App\Models\Setting::get('bcv_rate'));

        $this->actingAs($admin)->get("/supplier-invoices/{$invoice->id}")->assertOk()->assertSee('Equivale a USD');
    }

    public function test_invoice_in_bs_requires_the_emission_rate(): void
    {
        $admin = $this->user('admin');
        $party = Party::create(['name' => 'Proveedor', 'is_active' => true]);
        $supplier = Supplier::create(['party_id' => $party->id]);
        \App\Models\Setting::set('bcv_rate', 0);

        $this->actingAs($admin)->post('/supplier-invoices', [
            'supplier_id' => $supplier->id, 'date' => now()->toDateString(), 'currency' => 'VES', 'doc_type' => 'FC',
            'items' => [['quantity' => 1, 'unit_cost' => 100, 'description' => 'x']],
        ])->assertSessionHasErrors('bcv_rate_at_issue');
    }
}
