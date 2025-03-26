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

        // Check stock availability
        if ($product->stock < $request->quantity) {
            return response()->json(['error' => 'Not enough stock available'], 400);
        }

        // Add item to cart
        $cart = Cart::updateOrCreate(
            ['user_id' => Auth::id(), 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );

        return response()->json(['message' => 'Item added to cart', 'cart' => $cart], 201);
    }

    // View Cart
    public function viewCart()
    {
        $cart = Cart::where('user_id', Auth::id())->with('product')->get();
        return response()->json(['cart' => $cart]);
    }

    // Remove item from cart
    public function removeFromCart($id)
    {
        $cartItem = Cart::where('user_id', Auth::id())->where('id', $id)->first();
        if (!$cartItem) {
            return response()->json(['error' => 'Item not found in cart'], 404);
        }

        $cartItem->delete();
        return response()->json(['message' => 'Item removed from cart']);
    }
}
