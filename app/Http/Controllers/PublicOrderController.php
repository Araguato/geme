<?php

namespace App\Http\Controllers;

use App\Models\DeliveryInfo;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicOrderController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->paginate(24);

        return view('public_order.index', compact('products'));
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        $product = Product::findOrFail($request->product_id);
        $cart = session()->get('cart', []);

        $cart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $request->quantity,
        ];

        session()->put('cart', $cart);

        return redirect()->route('public.cart')
            ->with('success', __('Producto agregado al pedido.'));
    }

    public function cart()
    {
        $cart = session()->get('cart', []);
        return view('public_order.cart', compact('cart'));
    }

    public function removeFromCart(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $cart = session()->get('cart', []);
        unset($cart[$request->product_id]);
        session()->put('cart', $cart);

        return redirect()->route('public.cart')
            ->with('success', __('Producto eliminado del pedido.'));
    }

    public function checkout()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('public.order.index')
                ->with('error', __('Tu pedido está vacío.'));
        }

        return view('public_order.checkout', compact('cart'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'instructions' => 'nullable|string|max:1000',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('public.order.index')
                ->with('error', __('Tu pedido está vacío.'));
        }

        DB::transaction(function () use ($request, $cart) {
            $delivery = DeliveryInfo::create([
                'address' => $request->address,
                'city' => $request->city,
                'phone' => $request->customer_phone,
                'instructions' => $request->instructions,
                'status' => 'pending',
            ]);

            $subtotal = 0;
            $tax = 0;
            $total = 0;

            foreach ($cart as $item) {
                $lineTotal = $item['price'] * $item['quantity'];
                $subtotal += $lineTotal;
                $total += $lineTotal;
            }

            $order = Order::create([
                'order_number' => 'WEB-' . Str::upper(Str::random(8)),
                'user_id' => null,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'type' => 'online',
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => 0,
                'total' => $total,
                'notes' => 'Pedido realizado desde la tienda en línea.',
                'delivery_info_id' => $delivery->id,
                'cash_shift_id' => null,
            ]);

            foreach ($cart as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'discount' => 0,
                    'total' => $item['price'] * $item['quantity'],
                ]);
            }
        });

        session()->forget('cart');

        return redirect()->route('public.order.success')
            ->with('success', __('Tu pedido fue recibido. Te contactaremos para confirmar.'));
    }

    public function success()
    {
        return view('public_order.success');
    }
}
