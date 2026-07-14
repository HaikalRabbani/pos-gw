<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $query = Product::with('variants')
            ->where('outlet_id', $request->outlet_id);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:200',
            'price' => 'required|integer|min:0',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $product = Product::create($validated);

        return response()->json(['success' => true, 'data' => $product->load('variants')], 201);
    }

    public function show(Product $product)
    {
        return response()->json(['success' => true, 'data' => $product->load('variants')]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'sometimes|string|max:200',
            'price' => 'sometimes|integer|min:0',
            'image' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $product->update($validated);

        return response()->json(['success' => true, 'data' => $product->load('variants')]);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Product deleted.']);
    }
}
