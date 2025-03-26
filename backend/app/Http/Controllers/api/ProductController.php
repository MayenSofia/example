<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // Get all products (Open for everyone)
    public function index()
    {
        return response()->json(Product::all());
    }

    // Get a single product
    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['error' => 'Product not found'], 404);

        return response()->json($product);
    }

    // Store a new product (EMPLOYEE ONLY)
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'employee') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:products',
            'description' => 'required',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 400);

        $product = Product::create($request->all());

        return response()->json($product, 201);
    }

    // Update a product (EMPLOYEE ONLY)
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'employee') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $product = Product::find($id);
        if (!$product) return response()->json(['error' => 'Product not found'], 404);

        $product->update($request->all());

        return response()->json($product);
    }

    // Delete a product (EMPLOYEE ONLY, Prevent if ordered)
    public function destroy($id)
    {
        if (Auth::user()->role !== 'employee') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $product = Product::find($id);
        if (!$product) return response()->json(['error' => 'Product not found'], 404);

        if ($product->orderItems()->exists()) {
            return response()->json(['error' => 'Product cannot be deleted as it is in an order'], 400);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
