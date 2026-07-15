<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\Product;
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

        // ── 2 Categories ──
        $makanan = Category::create(['outlet_id' => $outlet->id, 'name' => 'Makanan', 'sort_order' => 1]);
        $minuman = Category::create(['outlet_id' => $outlet->id, 'name' => 'Minuman', 'sort_order' => 2]);

        // ── 2 Products ──
        $nasiGoreng = Product::create([
            'outlet_id' => $outlet->id, 'category_id' => $makanan->id,
            'name' => 'Nasi Goreng', 'price' => 35000, 'cost' => 12000,
            'is_active' => true, 'sort_order' => 0,
        ]);
        $esTeh = Product::create([
            'outlet_id' => $outlet->id, 'category_id' => $minuman->id,
            'name' => 'Es Teh Manis', 'price' => 7000, 'cost' => 1500,
            'is_active' => true, 'sort_order' => 0,
        ]);

        // ── 2 Taxes ──
        Tax::create(['outlet_id' => $outlet->id, 'name' => 'PPN 11%', 'rate' => 11, 'sort_order' => 2, 'is_active' => true]);
        Tax::create(['outlet_id' => $outlet->id, 'name' => 'Service Charge 5%', 'rate' => 5, 'sort_order' => 1, 'is_active' => true]);

        // ── 2 Discounts ──
        Discount::create(['outlet_id' => $outlet->id, 'name' => 'Diskon 10%', 'type' => 'percent', 'value' => 10, 'is_active' => true]);
        Discount::create(['outlet_id' => $outlet->id, 'name' => 'Diskon Rp 5.000', 'type' => 'nominal', 'value' => 500000, 'is_active' => true]);

        // ── 2 Orders (hari ini & kemarin) ──
        $products = [$nasiGoreng, $esTeh];

        for ($day = 1; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day)->setTime(rand(10, 18), rand(0, 59));

            $subtotal = ($nasiGoreng->price * 2) + ($esTeh->price * 1);
            $taxTotal = (int) round($subtotal * 11 / 100);
            $grandTotal = $subtotal + $taxTotal;

            $order = Order::create([
                'outlet_id' => $outlet->id,
                'user_id' => $cashier->id,
                'customer_name' => $day === 0 ? 'Budi' : 'Siti',
                'status' => 'done',
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $order->items()->createMany([
                [
                    'product_id' => $nasiGoreng->id,
                    'product_name' => $nasiGoreng->name,
                    'qty' => 2,
                    'unit_price' => $nasiGoreng->price,
                    'unit_cost' => $nasiGoreng->cost,
                    'total_price' => $nasiGoreng->price * 2,
                ],
                [
                    'product_id' => $esTeh->id,
                    'product_name' => $esTeh->name,
                    'qty' => 1,
                    'unit_price' => $esTeh->price,
                    'unit_cost' => $esTeh->cost,
                    'total_price' => $esTeh->price,
                ],
            ]);

            $order->payments()->create([
                'method' => 'cash',
                'amount' => $grandTotal,
                'paid_at' => $date,
            ]);
        }

        $this->command->info('✅ 2 dummy orders created.');
    }
}
