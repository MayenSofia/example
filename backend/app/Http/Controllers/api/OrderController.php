<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
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
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 400);

        $totalPrice = 0;
        $orderItems = [];

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);

            if ($product->stock < $item['quantity']) {
                return response()->json(['error' => "Not enough stock for product: {$product->name}"], 400);
            }

            $product->decrement('stock', $item['quantity']);
            $totalPrice += $product->price * $item['quantity'];

            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ];
        }

        $order = Order::create([
            'user_id' => $request->user()->id,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        foreach ($orderItems as $item) {
            $order->orderItems()->create($item);
        }

        return response()->json($order->load('orderItems.product'), 201);
    }
}
