<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicOrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Menu buat customer: kategori + produk aktif, khusus outlet yang
     * punya meja ini. outlet/table sudah ke-resolve dari qr_token lewat
     * middleware ResolveQrTable — customer gak perlu kirim outlet_id sendiri.
     */
    public function menu(Request $request)
    {
        $outlet = $request->attributes->get('qr_outlet');
        $table  = $request->attributes->get('qr_table');

        $categories = Category::where('outlet_id', $outlet->id)
            ->orderBy('sort_order')
            ->get();

        $products = Product::with('variants')
            ->where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $activeDiscounts = $this->getActiveDiscounts($outlet->id);

        $products = $products->map(function ($product) use ($activeDiscounts) {
            $product->discounted_price = $this->computeDiscountedPrice($product, $activeDiscounts);
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'outlet' => ['id' => $outlet->id, 'name' => $outlet->name],
                'table' => ['id' => $table->id, 'name' => $table->name],
                'categories' => $categories,
                'products' => $products,
            ],
        ]);
    }

    /**
     * Diskon aktif hari ini buat outlet ini, khusus yang bisa dipreview
     * per-produk (target product/category, bukan BOGO/transaction-wide).
     */
    protected function getActiveDiscounts(int $outletId)
    {
        $today = now()->toDateString();

        return Discount::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->whereIn('target_type', ['product', 'category'])
            ->whereNull('buy_x')
            ->whereNull('buy_y')
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->get();
    }

    /**
     * Preview harga setelah diskon (buat ditampilkan sebagai harga coret
     * di Self-Order). Ini cuma preview tampilan — kalkulasi final tetap
     * dihitung ulang oleh OrderService pas checkout, jadi selalu akurat
     * meskipun ada diskon lain (kombinasi produk, min_purchase, dst) yang
     * gak dimodelkan di preview sesimpel ini.
     */
    protected function computeDiscountedPrice(Product $product, $discounts): ?int
    {
        $bestReduction = 0;

        foreach ($discounts as $discount) {
            $ids = (array) $discount->target_id;

            if ($discount->target_type === 'product' && !in_array($product->id, $ids)) {
                continue;
            }
            if ($discount->target_type === 'category' && (!$product->category_id || !in_array($product->category_id, $ids))) {
                continue;
            }

            $reduction = $discount->type === 'percent'
                ? (int) round($product->price * $discount->value / 100)
                : (int) $discount->value;

            if ($discount->max_discount) {
                $reduction = min($reduction, (int) $discount->max_discount);
            }
            $reduction = min($reduction, $product->price);

            $bestReduction = max($bestReduction, $reduction);
        }

        return $bestReduction > 0 ? $product->price - $bestReduction : null;
    }

    /**
     * Detail kustomisasi produk (removable ingredients & addon) — versi
     * publik dari IngredientController::customize, dipakai pas customer
     * buka detail 1 menu sebelum add to cart.
     */
    public function customize(Request $request, Product $product)
    {
        $outlet = $request->attributes->get('qr_outlet');

        if ($product->outlet_id !== $outlet->id) {
            abort(404, 'Produk tidak ditemukan di outlet ini.');
        }

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

    /**
     * Bikin order baru buat meja ini. source otomatis 'self_order',
     * user_id null (customer gak perlu akun).
     */
    public function store(Request $request)
    {
        $outlet = $request->attributes->get('qr_outlet');
        $table  = $request->attributes->get('qr_table');

        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:100',
        ]);

        $order = $this->orderService->createDraft([
            'outlet_id'     => $outlet->id,
            'table_id'      => $table->id,
            'user_id'       => null,
            'customer_name' => $validated['customer_name'] ?? null,
            'source'        => 'self_order',
        ]);

        return response()->json(['success' => true, 'data' => $order], 201);
    }

    public function show(Request $request, Order $order)
    {
        $this->assertOwnedByTable($request, $order);

        return response()->json(['success' => true, 'data' => $order->load('items')]);
    }

    public function addItem(Request $request, Order $order)
    {
        $this->assertOwnedByTable($request, $order);

        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'variant_id'   => 'nullable|exists:product_variants,id',
            'product_name' => 'required|string|max:200',
            'variant_name' => 'nullable|string|max:100',
            'qty'          => 'required|integer|min:1',
            'unit_price'   => 'required|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        $order = $this->orderService->addItem($order, $validated);

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function removeItem(Request $request, Order $order, int $itemId)
    {
        $this->assertOwnedByTable($request, $order);

        $order = $this->orderService->removeItem($order, $itemId);

        return response()->json(['success' => true, 'data' => $order]);
    }

    /**
     * Customer selesai milih menu, kirim order ke dapur/kasir.
     * draft -> confirmed. Setelah ini order gak bisa diubah lagi lewat
     * endpoint publik (addItem/removeItem cuma izinin status draft).
     */
    public function submit(Request $request, Order $order)
    {
        $this->assertOwnedByTable($request, $order);

        if ($order->items()->count() === 0) {
            throw ValidationException::withMessages([
                'order' => ['Belum ada item di pesanan.'],
            ]);
        }

        $order = $this->orderService->updateStatus($order, 'confirmed', null, 'Dikirim oleh customer via self-order');

        return response()->json(['success' => true, 'data' => $order->load('items')]);
    }

    /**
     * Pastikan order ini emang milik meja yang lagi diakses (dari qr_token),
     * dan emang order self_order — biar customer gak bisa iseng akses/ubah
     * order staff lain cuma dengan nebak ID.
     */
    protected function assertOwnedByTable(Request $request, Order $order): void
    {
        $table = $request->attributes->get('qr_table');

        if ($order->table_id !== $table->id || $order->source !== 'self_order') {
            abort(404, 'Pesanan tidak ditemukan untuk meja ini.');
        }
    }
}
