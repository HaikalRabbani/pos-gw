<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $categories = Category::where('outlet_id', $request->outlet_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = Category::create($validated);

        return response()->json(['success' => true, 'data' => $category], 201);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category->update($validated);

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted.']);
    }
}
