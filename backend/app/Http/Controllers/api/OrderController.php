<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;
use App\Models\OrderItem; // ✅ Import Product model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // Get all orders for a customer
    public function index(Request $request)
    {
        return response()->json(Order::where('user_id', $request->user()->id)->with('orderItems.product')->get());
    }

    // Get a single order
    public function show($id)
    {
        $order = Order::with('orderItems.product')->find($id);
        if (!$order) return response()->json(['error' => 'Order not found'], 404);

        return response()->json($order);
    }

    // Checkout (Create an order)
    public function store(Request $request)
    {
        $user = $request->user();

        // Validate request
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // ✅ Ensure user has an 'orders()' relationship in User model
        $order = $user->orders()->create([
            'total_price' => collect($validated['items'])->sum(function ($item) {
                return Product::find($item['product_id'])->price * $item['quantity'];
            }),
            'status' => 'pending'
        ]);

        // ✅ Attach products to order
        foreach ($validated['items'] as $item) {
            $order->orderItems()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity']
            ]);
        }

        // ✅ Clear user's cart (ensure the cart relationship exists)
        if (method_exists($user, 'cart')) {
            $user->cart()->delete();
        }

        return response()->json(['message' => 'Order placed successfully', 'order' => $order], 201);
    }

    public function checkout()
    {
        $user = Auth::user();
        $cartItems = Cart::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        DB::beginTransaction();
        try {
            // Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_price' => $cartItems->sum(fn($item) => $item->product->price * $item->quantity)
            ]);

            // Transfer Cart Items to Order Items
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price
                ]);
            }

            // Clear the Cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();
            return response()->json(['message' => 'Order placed successfully!', 'order' => $order], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to process order'], 500);
        }
    }

}
