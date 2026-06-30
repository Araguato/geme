<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CashShift;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesLocation;
use App\Services\FiscalLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $activeShift = CashShift::where('is_active', true)->latest()->first();
        $products = Product::where('is_active', true)
            ->where('is_raw_material', false)
            ->with(['images', 'mainImage'])
            ->orderBy('name')
            ->get();

        $customers = Party::where('is_active', true)
            ->whereNotNull('document_number')
            ->orderBy('name')
            ->get(['id', 'name', 'document_number', 'document_type']);

        $salesLocations = SalesLocation::where('is_active', true)->orderBy('name')->get();

        return view('pos.index', compact('activeShift', 'products', 'customers', 'salesLocations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_party_id' => 'nullable|exists:parties,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment.method' => 'required|in:cash,card,transfer,other',
            'payment.amount' => 'required|numeric|min:0',
            'payment.reference' => 'nullable|string|max:255',
        ]);

        $activeShift = CashShift::where('is_active', true)->latest()->first();

        $order = DB::transaction(function () use ($data, $activeShift) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => Auth::id(),
                'sales_location_id' => $activeShift?->sales_location_id,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_party_id' => $data['customer_party_id'] ?? null,
                'type' => 'pos',
                'status' => 'completed',
                'payment_status' => 'paid',
                'subtotal' => 0,
                'tax' => 0,
                'discount' => 0,
                'total' => 0,
                'cash_shift_id' => $activeShift?->id,
            ]);

            $subtotal = 0;
            $tax = 0;

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $taxRate = (float) ($product->tax_rate ?? 0);
                $lineSubtotal = round($qty * $price, 2);
                $lineTax = round($lineSubtotal * ($taxRate / 100), 2);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $lineSubtotal + $lineTax,
                ]);

                $subtotal += $lineSubtotal;
                $tax += $lineTax;
            }

            $total = round($subtotal + $tax, 2);

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'cash_shift_id' => $activeShift?->id,
                'method' => $data['payment']['method'],
                'amount' => $data['payment']['amount'],
                'reference' => $data['payment']['reference'] ?? null,
            ]);

            return $order;
        });

        FiscalLedgerService::recordSale($order);

        return redirect()->route('pos.ticket', $order)->with('success', 'Venta registrada correctamente.');
    }

    public function ticket(Order $order)
    {
        $order->load(['items.product', 'salesLocation', 'user']);
        return view('pos.ticket', compact('order'));
    }

    private function generateOrderNumber(): string
    {
        return 'V-' . now()->format('Ymd') . '-' . strtoupper(uniqid());
    }
}
