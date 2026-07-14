<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $outlet = Outlet::where('name', 'Outlet Pusat')->first();
        if (!$outlet) {
            $this->command->warn('Outlet Pusat not found. Run DatabaseSeeder first.');
            return;
        }

        $cashier = User::where('email', 'kasir@pos.com')->first();
        if (!$cashier) {
            $this->command->warn('Kasir not found. Run DatabaseSeeder first.');
            return;
        }

        // ──────────────────────────────────────────────
        // 1. Categories
        // ──────────────────────────────────────────────
        $makanan = Category::create([
            'outlet_id' => $outlet->id,
            'name' => 'Makanan',
            'sort_order' => 1,
        ]);
        $minuman = Category::create([
            'outlet_id' => $outlet->id,
            'name' => 'Minuman',
            'sort_order' => 2,
        ]);
        $cemilan = Category::create([
            'outlet_id' => $outlet->id,
            'name' => 'Cemilan',
            'sort_order' => 3,
        ]);

        // ──────────────────────────────────────────────
        // 2. Products with cost (HPP)
        // ──────────────────────────────────────────────
        $products = [
            // Makanan
            ['category_id' => $makanan->id, 'name' => 'Nasi Goreng',           'price' => 35000, 'cost' => 12000],
            ['category_id' => $makanan->id, 'name' => 'Mie Goreng',            'price' => 30000, 'cost' => 10000],
            ['category_id' => $makanan->id, 'name' => 'Ayam Bakar',            'price' => 45000, 'cost' => 18000],
            ['category_id' => $makanan->id, 'name' => 'Sate Ayam (10 tusuk)',  'price' => 40000, 'cost' => 15000],
            ['category_id' => $makanan->id, 'name' => 'Gado-Gado',             'price' => 28000, 'cost' => 9000],
            ['category_id' => $makanan->id, 'name' => 'Soto Ayam',             'price' => 32000, 'cost' => 11000],
            ['category_id' => $makanan->id, 'name' => 'Nasi Uduk',             'price' => 25000, 'cost' => 8000],
            ['category_id' => $makanan->id, 'name' => 'Ikan Bakar',            'price' => 50000, 'cost' => 22000],
            // Minuman
            ['category_id' => $minuman->id, 'name' => 'Es Teh Manis',           'price' => 7000,  'cost' => 1500],
            ['category_id' => $minuman->id, 'name' => 'Es Jeruk',              'price' => 8000,  'cost' => 2000],
            ['category_id' => $minuman->id, 'name' => 'Kopi Hitam',            'price' => 12000, 'cost' => 3000],
            ['category_id' => $minuman->id, 'name' => 'Jus Alpukat',           'price' => 18000, 'cost' => 6000],
            ['category_id' => $minuman->id, 'name' => 'Air Mineral',           'price' => 5000,  'cost' => 1000],
            ['category_id' => $minuman->id, 'name' => 'Milkshake Coklat',      'price' => 22000, 'cost' => 8000],
            // Cemilan
            ['category_id' => $cemilan->id, 'name' => 'Kentang Goreng',        'price' => 15000, 'cost' => 5000],
            ['category_id' => $cemilan->id, 'name' => 'Pisang Goreng (5 pcs)', 'price' => 12000, 'cost' => 4000],
            ['category_id' => $cemilan->id, 'name' => 'Lumpia (5 pcs)',        'price' => 18000, 'cost' => 7000],
            ['category_id' => $cemilan->id, 'name' => 'Tahu Isi (5 pcs)',      'price' => 10000, 'cost' => 3500],
        ];

        $productModels = [];
        foreach ($products as $p) {
            $productModels[$p['name']] = Product::create([
                'outlet_id' => $outlet->id,
                'category_id' => $p['category_id'],
                'name' => $p['name'],
                'price' => $p['price'],
                'cost' => $p['cost'],
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        // ──────────────────────────────────────────────
        // 3. Taxes & Discounts
        // ──────────────────────────────────────────────
        $ppn = Tax::create([
            'outlet_id' => $outlet->id,
            'name' => 'PPN 11%',
            'rate' => 11,
            'is_active' => true,
        ]);

        $diskon10 = Discount::create([
            'outlet_id' => $outlet->id,
            'name' => 'Diskon 10%',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        // ──────────────────────────────────────────────
        // 4. Generate Orders for the past 30 days
        // ──────────────────────────────────────────────
        $this->command->info('Generating 30 days of transaction data...');

        $now = Carbon::now();
        $orderCount = 0;

        for ($day = 29; $day >= 0; $day--) {
            $date = $now->copy()->subDays($day);
            $dateStr = $date->format('Y-m-d');

            // Random number of orders per day (3-12)
            $ordersToday = rand(3, 12);

            for ($i = 0; $i < $ordersToday; $i++) {
                $hour = rand(8, 21);
                $minute = rand(0, 59);
                $createdAt = $date->copy()->setTime($hour, $minute, rand(0, 59));

                // Pick 2-5 random products
                $selectedProducts = collect($productModels)->random(rand(2, 5));
                $subtotal = 0;
                $items = [];

                foreach ($selectedProducts as $name => $product) {
                    $qty = rand(1, 3);
                    $totalPrice = $product->price * $qty;
                    $subtotal += $totalPrice;

                    $items[] = [
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'product_name' => $product->name,
                        'variant_name' => null,
                        'qty' => $qty,
                        'unit_price' => $product->price,
                        'unit_cost' => $product->cost,
                        'total_price' => $totalPrice,
                        'notes' => null,
                    ];
                }

                $taxTotal = (int) round($subtotal * 11 / 100);
                $discountTotal = rand(0, 1) ? (int) round($subtotal * 10 / 100) : 0;
                $grandTotal = $subtotal + $taxTotal - $discountTotal;

                $statuses = ['done', 'done', 'done', 'done', 'cancelled'];
                $status = $statuses[array_rand($statuses)];
                $paymentMethod = rand(0, 3) > 0 ? 'cash' : 'midtrans';
                $paymentStatus = $status === 'cancelled' ? 'unpaid' : 'paid';

                $order = Order::create([
                    'outlet_id' => $outlet->id,
                    'table_id' => null,
                    'user_id' => $cashier->id,
                    'customer_name' => rand(0, 1) ? fake()->name() : null,
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'grand_total' => $grandTotal,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'notes' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Create order items
                foreach ($items as $item) {
                    $order->items()->create($item);
                }

                // Create payment record if paid
                if ($paymentStatus === 'paid') {
                    $order->payments()->create([
                        'method' => $paymentMethod,
                        'amount' => $grandTotal,
                        'paid_at' => $createdAt,
                    ]);
                }

                $orderCount++;
            }
        }

        $this->command->info("✅ Generated {$orderCount} dummy orders with items & payments.");
    }
}
