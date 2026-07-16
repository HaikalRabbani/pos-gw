<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $ingredients = Ingredient::where('outlet_id', $request->outlet_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $ingredients]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $ingredient = Ingredient::create($validated);

        return response()->json(['success' => true, 'data' => $ingredient], 201);
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $ingredient->update($validated);

        return response()->json(['success' => true, 'data' => $ingredient]);
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        return response()->json(['success' => true, 'message' => 'Ingredient deleted.']);
    }

    // ── Product Ingredient Sync ──

    /**
     * Get product customization: all ingredients linked to this product.
     */
    public function productIngredients(Product $product)
    {
        $ingredients = $product->ingredients()->get()->map(fn($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'is_removable' => (bool) $i->pivot->is_removable,
            'extra_price' => (int) $i->pivot->extra_price,
            'is_default' => (bool) $i->pivot->is_default,
            'sort_order' => (int) $i->pivot->sort_order,
        ]);

        $allIngredients = Ingredient::where('outlet_id', $product->outlet_id)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'assigned' => $ingredients,
                'available' => $allIngredients,
            ],
        ]);
    }

    /**
     * Sync all ingredients for a product.
     * Expects: { ingredients: [{ ingredient_id, is_removable, extra_price, is_default, sort_order }] }
     */
    public function syncProductIngredients(Request $request, Product $product)
    {
        $validated = $request->validate([
            'ingredients' => 'required|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.is_removable' => 'sometimes|boolean',
            'ingredients.*.extra_price' => 'sometimes|integer|min:0',
            'ingredients.*.is_default' => 'sometimes|boolean',
            'ingredients.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $syncData = [];
        foreach ($validated['ingredients'] as $item) {
            $syncData[$item['ingredient_id']] = [
                'is_removable' => $item['is_removable'] ?? true,
                'extra_price' => $item['extra_price'] ?? 0,
                'is_default' => $item['is_default'] ?? true,
                'sort_order' => $item['sort_order'] ?? 0,
            ];
        }

        $product->ingredients()->sync($syncData);

        return response()->json([
            'success' => true,
            'message' => 'Product ingredients updated.',
        ]);
    }

    /**
     * Available ingredients list for customize endpoint (Self-Order).
     * Grouped into removable (can exclude) and addons (extra charge).
     */
    public function customize(Product $product)
    {
        $product->load('removableIngredients', 'addons');

        return response()->json([
            'success' => true,
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'description' => $product->description,
                    'image' => $product->image,
                ],
                'removable_ingredients' => $product->removableIngredients->map(fn($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'is_default' => (bool) $i->pivot->is_default,
                ]),
                'addons' => $product->addons->map(fn($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'extra_price' => (int) $i->pivot->extra_price,
                    'is_default' => (bool) $i->pivot->is_default,
                ]),
            ],
        ]);
    }
}
