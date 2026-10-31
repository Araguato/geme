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
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $activeShift = CashShift::activeFor(Auth::user());
        $products = Product::where('is_active', true)
            ->where('is_raw_material', false)
            ->with(['images', 'mainImage', 'parent', 'barcodes', 'stockItems.warehouse', 'stockItems.location'])
            ->where(function ($q) {
                $q->whereNull('parent_product_id')->whereDoesntHave('variants')
                  ->orWhereNotNull('parent_product_id');
            })
            ->orderBy('name')
            ->get();

        $customers = Party::where('is_active', true)
            ->whereNotNull('document_number')
            ->orderBy('name')
            ->get(['id', 'name', 'document_number', 'document_type']);

        $salesLocations = SalesLocation::where('is_active', true)->orderBy('name')->get();

        $paymentSummary = [];
        $casheaInitial = 0;
        $casheaFinanced = 0;
        $totalReceived = 0;
        if ($activeShift) {
            $payments = Payment::where('cash_shift_id', $activeShift->id)->get();
            foreach ($payments as $payment) {
                $method = $payment->method;
                $amount = (float) $payment->amount;
                $paymentSummary[$method] = ($paymentSummary[$method] ?? 0) + $amount;
                $totalReceived += $amount;
                if ($method === 'cashea') {
                    $casheaInitial += $amount;
                    $casheaFinanced += (float) ($payment->cashea_financed_amount ?? 0);
                }
            }
        }

        // Depósito del negocio donde está abierta la caja: el TPV solo vende ese stock
        $shopWarehouseId = $activeShift?->salesLocation?->warehouse_id;
        $canChangePrice = $this->canChangePrice();

        return view('pos.index', compact(
            'activeShift', 'products', 'customers', 'salesLocations',
            'paymentSummary', 'casheaInitial', 'casheaFinanced', 'totalReceived',
            'shopWarehouseId', 'canChangePrice'
        ));
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
            'items.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'items.*.location_id' => 'nullable|exists:locations,id',
            'payment.method' => 'required|in:cash,card,transfer,pagomovil,punto,cashea,other',
            'payment.amount' => 'required|numeric|min:0',
            'payment.reference' => 'nullable|string|max:255',
            'payment.cashea_initial_percentage' => 'nullable|required_if:payment.method,cashea|numeric|min:0|max:100',
        ]);

        $isCashea = $data['payment']['method'] === 'cashea';
        $orderTotal = 0;

        $activeShift = CashShift::activeFor(Auth::user());
        if (!$activeShift) {
            return redirect()->route('pos.index')->with('error', 'Abre tu turno de caja antes de registrar ventas.');
        }

        $canChangePrice = $this->canChangePrice();
        $shopWarehouseId = $activeShift->salesLocation?->warehouse_id;

        $order = DB::transaction(function () use ($data, $activeShift, $isCashea, $canChangePrice, $shopWarehouseId) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => Auth::id(),
                'sales_location_id' => $activeShift->sales_location_id,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_party_id' => $data['customer_party_id'] ?? null,
                'type' => 'pos',
                'status' => 'completed',
                'payment_status' => 'paid',
                'subtotal' => 0,
                'tax' => 0,
                'discount' => 0,
                'total' => 0,
                'cash_shift_id' => $activeShift->id,
            ]);

            $subtotal = 0;
            $tax = 0;

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                $qty = (float) $item['quantity'];
                // El precio enviado solo se acepta si quien vende puede cambiarlo
                $price = ($canChangePrice || $product->is_price_change_allowed)
                    ? (float) $item['unit_price']
                    : (float) $product->price;
                $taxRate = (float) ($product->tax_rate ?? 0);
                $lineSubtotal = round($qty * $price, 2);
                $lineTax = round($lineSubtotal * ($taxRate / 100), 2);

                // La línea guarda de dónde salió realmente el stock, no lo que mandó el formulario
                $stockItem = InventoryService::consumeSaleItem(
                    $product,
                    $qty,
                    $item['warehouse_id'] ?? null,
                    $item['location_id'] ?? null,
                    $order,
                    Auth::id(),
                    $shopWarehouseId
                );

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $lineSubtotal + $lineTax,
                    'warehouse_id' => $stockItem?->warehouse_id,
                    'location_id' => $stockItem?->location_id,
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

            // Lo cobrado es el total calculado aquí: solo Cashea cobra una parte
            $paymentData = [
                'order_id' => $order->id,
                'cash_shift_id' => $activeShift->id,
                'method' => $data['payment']['method'],
                'amount' => $total,
                'reference' => $data['payment']['reference'] ?? null,
            ];

            if ($isCashea) {
                $percentage = (float) ($data['payment']['cashea_initial_percentage'] ?? 0);
                $initialAmount = round($total * ($percentage / 100), 2);
                $financedAmount = round($total - $initialAmount, 2);
                $paymentData['amount'] = $initialAmount;
                $paymentData['cashea_initial_percentage'] = $percentage;
                $paymentData['cashea_financed_amount'] = $financedAmount;
            }

            Payment::create($paymentData);

            return $order;
        });

        FiscalLedgerService::recordSale($order);

        return redirect()->route('pos.index')->with('success', 'Venta registrada correctamente.');
    }

    public function ticket(Order $order)
    {
        $order->load(['items.product', 'salesLocation', 'user']);
        return view('pos.ticket', compact('order'));
    }

    /**
     * Admin y supervisor pueden cambiar el precio de cualquier línea; el resto solo
     * en productos marcados con "Cambio de precio permitido".
     */
    private function canChangePrice(): bool
    {
        $user = Auth::user();

        return $user !== null && $user->hasAnyRole(['admin', 'supervisor']);
    }

    private function generateOrderNumber(): string
    {
        return 'V-' . now()->format('Ymd') . '-' . strtoupper(uniqid());
    }
}
