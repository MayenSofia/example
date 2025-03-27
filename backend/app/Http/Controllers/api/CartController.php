<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // Add item to cart
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        $userId = Auth::id();

        // Check if item already in cart
        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->first();

        if ($product->stock < ($cartItem ? $cartItem->quantity + $request->quantity : $request->quantity)) {
            return response()->json(['error' => 'Not enough stock available'], 400);
        }

        // Update or create cart item
        if ($cartItem) {
            $cartItem->increment('quantity', $request->quantity);
        } else {
            $cartItem = Cart::create([
                'user_id' => $userId,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        // Deduct stock
        $product->decrement('stock', $request->quantity);

        return response()->json(['message' => 'Item added to cart', 'cart' => $cartItem], 201);
    }

    // View Cart
    public function viewCart()
    {
        $cart = Cart::where('user_id', Auth::id())->with('product')->get();
        return response()->json(['cart' => $cart]);
    }

    // Remove item from cart and restore stock
    public function removeFromCart($id)
    {
        $cartItem = Cart::where('user_id', Auth::id())->where('id', $id)->first();
        if (!$cartItem) {
            return response()->json(['error' => 'Item not found in cart'], 404);
        }

        // Restore stock
        $cartItem->product->increment('stock', $cartItem->quantity);

        $cartItem->delete();
        return response()->json(['message' => 'Item removed from cart']);
    }

    public function clearCart()
    {
        $userId = Auth::id();
        $cartItems = Cart::where('user_id', $userId)->get();

        foreach ($cartItems as $cartItem) {
            // Restore stock before deleting
            $cartItem->product->increment('stock', $cartItem->quantity);
            $cartItem->delete();
        }

        return response()->json(['message' => 'Cart cleared successfully']);
    }

    // Update Cart Item Quantity
    public function updateCartItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem = Cart::where('user_id', Auth::id())->where('id', $id)->first();
        if (!$cartItem) {
            return response()->json(['error' => 'Item not found in cart'], 404);
        }

        $product = Product::findOrFail($cartItem->product_id);

        $newQuantity = $request->quantity;
        $stockChange = $newQuantity - $cartItem->quantity;

        if ($stockChange > 0 && $product->stock < $stockChange) {
            return response()->json(['error' => 'Not enough stock available'], 400);
        }

        // Adjust stock accordingly
        $product->decrement('stock', $stockChange);
        $cartItem->update(['quantity' => $newQuantity]);

        return response()->json(['message' => 'Cart item updated', 'cart' => $cartItem]);
    }
}
