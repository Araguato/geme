<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Category;
use App\Models\FiscalLedger;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesLocation;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesLedgerAmountsTest extends TestCase
{
    use RefreshDatabase;

    private function sellMixed(): Order
    {
        $category = Category::create(['name' => 'General', 'is_active' => true]);
        $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Principal', 'is_active' => true]);
        $location = Location::create(['warehouse_id' => $warehouse->id, 'code' => 'G', 'name' => 'General', 'is_active' => true]);
        $shop = SalesLocation::create(['name' => 'Tienda', 'warehouse_id' => $warehouse->id, 'is_active' => true]);
        $taxed = Product::create(['category_id' => $category->id, 'name' => 'Con IVA', 'price' => 10, 'tax_rate' => 16, 'is_active' => true]);
        $exempt = Product::create(['category_id' => $category->id, 'name' => 'Exento', 'price' => 5, 'tax_rate' => 0, 'is_active' => true]);
        foreach ([$taxed, $exempt] as $p) {
            StockItem::create(['product_id' => $p->id, 'warehouse_id' => $warehouse->id, 'location_id' => $location->id, 'quantity' => 10, 'average_cost' => 1]);
            InventoryService::syncStockQuantity($p);
        }

        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'cajero'])->id);
        CashShift::create(['user_id' => $user->id, 'sales_location_id' => $shop->id, 'opened_at' => now(), 'opening_amount' => 0, 'is_active' => true]);

        $this->actingAs($user)->post('/pos', [
            'items' => [
                ['product_id' => $taxed->id, 'quantity' => 2, 'unit_price' => 10],
                ['product_id' => $exempt->id, 'quantity' => 1, 'unit_price' => 5],
            ],
            'payment' => ['method' => 'cash', 'amount' => 28.2],
        ])->assertSessionHasNoErrors();

        return Order::latest('id')->firstOrFail();
    }

    public function test_sales_ledger_splits_taxable_base_exempt_tax_and_total(): void
    {
        $order = $this->sellMixed();
        $entry = FiscalLedger::where('related_type', Order::class)->where('related_id', $order->id)->firstOrFail();

        $this->assertEquals(20.00, (float) $entry->taxable_amount);
        $this->assertEquals(5.00, (float) $entry->exempt_amount);
        $this->assertEquals(3.20, (float) $entry->tax_amount);
        $this->assertEquals(28.20, (float) $entry->total_amount);
        $this->assertEquals(16.0, (float) $entry->tax_rate);
    }

    public function test_repair_command_fixes_old_entries_and_keeps_rate_and_numbers(): void
    {
        $order = $this->sellMixed();
        $entry = FiscalLedger::where('related_type', Order::class)->where('related_id', $order->id)->firstOrFail();
        $number = $entry->document_number;

        // Asiento como lo dejaba el fallo antiguo: base y exento en 0
        $entry->forceFill(['taxable_amount' => 0, 'exempt_amount' => 0, 'exchange_rate' => 917.7751])->save();

        $this->artisan('fiscal:repair-sales-ledger')->assertSuccessful();
        $this->assertEquals(0, (float) $entry->fresh()->taxable_amount, 'sin --apply no cambia nada');

        $this->artisan('fiscal:repair-sales-ledger', ['--apply' => true])->assertSuccessful();
        $entry->refresh();
        $this->assertEquals(20.00, (float) $entry->taxable_amount);
        $this->assertEquals(5.00, (float) $entry->exempt_amount);
        $this->assertEquals(917.7751, (float) $entry->exchange_rate);
        $this->assertSame($number, $entry->document_number);
    }
}
