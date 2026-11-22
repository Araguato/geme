<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleHomeAndSalesReportTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRoles(array $roles, array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        foreach ($roles as $role) {
            $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);
        }

        return $user;
    }

    private function login(User $user)
    {
        return $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_contable_role_exists_after_migrations(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'contable']);
    }

    public function test_each_role_lands_on_its_own_home_after_login(): void
    {
        $cases = [
            'admin' => '/dashboard',
            'supervisor' => '/dashboard',
            'contable' => '/contabilidad',
            'vendedor' => '/pos',
            'cajero' => '/pos',
            'almacenista' => '/almacen',
            'cliente' => '/pedido',
        ];

        foreach ($cases as $role => $home) {
            $user = $this->userWithRoles([$role]);
            $this->login($user)->assertRedirect($home);
            $this->post('/logout');
        }
    }

    public function test_seller_who_is_also_warehouse_keeper_lands_on_pos(): void
    {
        $this->login($this->userWithRoles(['almacenista', 'vendedor']))->assertRedirect('/pos');
    }

    public function test_home_link_redirects_by_role(): void
    {
        $this->actingAs($this->userWithRoles(['contable']))->get('/inicio')->assertRedirect('/contabilidad');
        $this->actingAs($this->userWithRoles(['almacenista']))->get('/')->assertRedirect('/almacen');
    }

    public function test_contable_sees_finance_but_not_sales_inventory_or_admin(): void
    {
        $user = $this->userWithRoles(['contable']);

        $this->actingAs($user)->get('/contabilidad')->assertOk()->assertSee('Panel financiero');
        $this->actingAs($user)->get('/reports/sales')->assertOk();
        $this->actingAs($user)->get('/fiscal-ledger')->assertOk();
        $this->actingAs($user)->get('/supplier-ap/dashboard')->assertOk();
        $this->actingAs($user)->get('/supplier-invoices')->assertOk();
        $this->actingAs($user)->get('/settings/bcv')->assertOk();

        $this->actingAs($user)->get('/pos')->assertForbidden();
        $this->actingAs($user)->get('/products')->assertForbidden();
        $this->actingAs($user)->get('/stock/adjust')->assertForbidden();
        $this->actingAs($user)->get('/users')->assertForbidden();
        $this->actingAs($user)->get('/payroll-periods')->assertForbidden();
        $this->actingAs($user)->get('/settings/company')->assertForbidden();
        $this->actingAs($user)->get('/backup/database')->assertForbidden();
    }

    public function test_sales_and_warehouse_roles_cannot_see_reports_or_finance(): void
    {
        foreach (['vendedor', 'cajero', 'almacenista', 'despachador'] as $role) {
            $user = $this->userWithRoles([$role]);
            $this->actingAs($user)->get('/reports/sales')->assertForbidden();
            $this->actingAs($user)->get('/reports/sales/export')->assertForbidden();
            $this->actingAs($user)->get('/contabilidad')->assertForbidden();
        }

        $this->actingAs($this->userWithRoles(['vendedor']))->get('/almacen')->assertForbidden();
        $this->actingAs($this->userWithRoles(['almacenista']))->get('/almacen')->assertOk()->assertSee('Almacén');
    }

    public function test_warehouse_dashboard_shows_low_stock_without_prices(): void
    {
        $category = Category::create(['name' => 'Víveres', 'is_active' => true]);
        Product::create([
            'category_id' => $category->id, 'name' => 'Harina PAN', 'price' => 1.5, 'cost' => 1.1,
            'is_active' => true, 'is_stock_tracked' => true, 'stock_quantity' => 2, 'reorder_point' => 10,
        ]);

        $this->actingAs($this->userWithRoles(['almacenista']))->get('/almacen')
            ->assertOk()
            ->assertSee('Harina PAN')
            ->assertDontSee('1,10')
            ->assertDontSee('1.50');
    }

    /** Dos vendedores, dos categorías; el costo de un producto sale del kardex. */
    private function seedSales(): array
    {
        $ana = $this->userWithRoles(['vendedor'], ['name' => 'Ana']);
        $luis = $this->userWithRoles(['vendedor'], ['name' => 'Luis']);
        $viveres = Category::create(['name' => 'Víveres', 'is_active' => true]);
        $limpieza = Category::create(['name' => 'Limpieza', 'is_active' => true]);
        $harina = Product::create(['category_id' => $viveres->id, 'name' => 'Harina', 'price' => 10, 'cost' => 6, 'is_active' => true]);
        $jabon = Product::create(['category_id' => $limpieza->id, 'name' => '=Jabón', 'price' => 5, 'cost' => 2, 'is_active' => true]);

        $sale = function (User $seller, array $lines, string $status = 'completed', ?string $date = null) {
            $order = Order::create([
                'order_number' => uniqid('T'), 'user_id' => $seller->id, 'type' => 'pos', 'status' => $status,
                'payment_status' => 'paid', 'subtotal' => 0, 'tax' => 0, 'total' => 0,
            ]);
            $sub = $tax = 0;
            foreach ($lines as [$product, $qty, $kardexCost]) {
                $net = $qty * $product->price;
                $lineTax = round($net * 0.16, 2);
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $product->id, 'name' => $product->name,
                    'quantity' => $qty, 'unit_price' => $product->price, 'tax_rate' => 16,
                    'tax_amount' => $lineTax, 'total' => $net + $lineTax,
                ]);
                if ($kardexCost !== null) {
                    StockMovement::create([
                        'product_id' => $product->id, 'movement_date' => now(), 'type' => 'out', 'reason' => 'sale',
                        'quantity' => -$qty, 'unit_cost' => $kardexCost, 'total_cost' => -$qty * $kardexCost,
                        'reference_type' => 'order', 'reference_id' => $order->id,
                    ]);
                }
                $sub += $net;
                $tax += $lineTax;
            }
            $order->update(['subtotal' => $sub, 'tax' => $tax, 'total' => $sub + $tax]);
            if ($date) {
                $order->created_at = $date;
                $order->save();
            }

            return $order;
        };

        // Ana: 2 harinas (costo kardex 7) + 1 jabón (sin kardex → costo del producto 2)
        $sale($ana, [[$harina, 2, 7.0], [$jabon, 1, null]]);
        // Luis: 4 jabones (sin kardex)
        $sale($luis, [[$jabon, 4, null]]);
        // No cuentan: anulada y fuera del período
        $sale($luis, [[$harina, 100, null]], 'cancelled');
        $sale($ana, [[$harina, 50, null]], 'completed', now()->subYears(2)->toDateTimeString());

        return compact('ana', 'luis', 'viveres', 'limpieza');
    }

    public function test_sales_report_by_seller_category_and_product(): void
    {
        $d = $this->seedSales();
        $admin = $this->userWithRoles(['admin']);

        $bySeller = $this->actingAs($admin)->get('/reports/sales?group=seller')->assertOk()->viewData('rows')->keyBy('label');
        // Ana: neta 25, costo 2*7 + 1*2 = 16, margen 9
        $this->assertEqualsWithDelta(25, $bySeller['Ana']['net'], 0.001);
        $this->assertEqualsWithDelta(16, $bySeller['Ana']['cost'], 0.001);
        $this->assertEqualsWithDelta(9, $bySeller['Ana']['margin'], 0.001);
        $this->assertSame(1, $bySeller['Ana']['tickets']);
        // Luis: neta 20, costo 8
        $this->assertEqualsWithDelta(20, $bySeller['Luis']['net'], 0.001);
        $this->assertEqualsWithDelta(8, $bySeller['Luis']['cost'], 0.001);

        $totals = $this->actingAs($admin)->get('/reports/sales')->viewData('totals');
        $this->assertSame(2, $totals['tickets']);
        $this->assertEqualsWithDelta(45, $totals['net'], 0.001);
        $this->assertEqualsWithDelta(7.2, $totals['tax'], 0.001);
        $this->assertEqualsWithDelta(24, $totals['cost'], 0.001);

        $byCategory = $this->actingAs($admin)->get('/reports/sales?group=category')->viewData('rows')->keyBy('label');
        $this->assertEqualsWithDelta(20, $byCategory['Víveres']['net'], 0.001);
        $this->assertEqualsWithDelta(25, $byCategory['Limpieza']['net'], 0.001);
        $this->assertEqualsWithDelta(5, $byCategory['Limpieza']['quantity'], 0.001);

        // Filtro por vendedor + categoría
        $rows = $this->actingAs($admin)
            ->get("/reports/sales?group=category&user_id={$d['ana']->id}&category_id={$d['limpieza']->id}")
            ->viewData('rows');
        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(5, $rows->first()['net'], 0.001);

        $byProduct = $this->actingAs($admin)->get('/reports/sales?group=product')->viewData('rows');
        $this->assertSame('=Jabón', $byProduct->first()['label']);
    }

    public function test_csv_export_is_audited_and_escapes_formulas(): void
    {
        $this->seedSales();
        $contable = $this->userWithRoles(['contable']);

        $response = $this->actingAs($contable)->get('/reports/sales/export?group=product');
        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString("'=Jabón", $csv);
        $this->assertStringContainsString('Venta neta', $csv);
        $this->assertTrue(AuditLog::where('user_id', $contable->id)->where('action', 'report.sales.export')->exists());
    }

    public function test_admin_dashboard_shows_ranking_and_real_open_payables(): void
    {
        $this->seedSales();

        $this->actingAs($this->userWithRoles(['admin']))->get('/dashboard')
            ->assertOk()
            ->assertSee('Por vendedor')
            ->assertSee('Ana')
            ->assertSee('Informe de ventas');

        $this->actingAs($this->userWithRoles(['contable']))->get('/contabilidad')
            ->assertOk()
            ->assertSee('Ventas por vendedor')
            ->assertSee('Luis');

        // El vendedor no ve las estadísticas
        $this->actingAs($this->userWithRoles(['vendedor']))->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Informe de ventas');
    }

    public function test_sales_without_cost_are_not_counted_as_full_margin(): void
    {
        $seller = $this->userWithRoles(['vendedor'], ['name' => 'Ian']);
        $category = Category::create(['name' => 'Varios', 'is_active' => true]);
        $noCost = Product::create(['category_id' => $category->id, 'name' => 'Sin costo', 'price' => 10, 'is_active' => true]);
        $withCost = Product::create(['category_id' => $category->id, 'name' => 'Con costo', 'price' => 10, 'cost' => 6, 'is_active' => true]);

        $order = Order::create(['order_number' => 'T1', 'user_id' => $seller->id, 'type' => 'pos', 'status' => 'completed',
            'payment_status' => 'paid', 'subtotal' => 30, 'tax' => 0, 'total' => 30]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $noCost->id, 'name' => 'Sin costo', 'quantity' => 2, 'unit_price' => 10, 'tax_amount' => 0, 'total' => 20]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $withCost->id, 'name' => 'Con costo', 'quantity' => 1, 'unit_price' => 10, 'tax_amount' => 0, 'total' => 10]);

        $admin = $this->userWithRoles(['admin']);
        $totals = $this->actingAs($admin)->get('/reports/sales')->assertOk()->assertSee('sin costo registrado')->viewData('totals');

        // Solo la línea con costo entra en el margen: 10 - 6 = 4 → 40 %
        $this->assertEqualsWithDelta(20, $totals['uncosted'], 0.001);
        $this->assertEqualsWithDelta(33.3, $totals['cost_coverage'], 0.1);
        $this->assertEqualsWithDelta(4, $totals['margin'], 0.001);
        $this->assertEqualsWithDelta(40, $totals['margin_pct'], 0.001);

        $products = $this->actingAs($admin)->get('/reports/sales?group=product')->viewData('rows')->keyBy('label');
        $this->assertNull($products['Sin costo']['margin_pct']);

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Ian')->assertSee('sin costo');
    }
}
