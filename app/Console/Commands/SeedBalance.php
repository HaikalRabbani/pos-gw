<?php

namespace App\Console\Commands;

use App\Models\BalanceTransaction;
use App\Models\Outlet;
use App\Models\OutletBalance;
use Illuminate\Console\Command;

class SeedBalance extends Command
{
    protected $signature = 'pos:seed-balance {amount=50000000}';
    protected $description = 'Isi dummy balance QRIS untuk semua outlet (testing withdraw)';

    public function handle(): void
    {
        $amount = (int) $this->argument('amount'); // dalam cents, default Rp 500.000

        $outlets = Outlet::all();

        if ($outlets->isEmpty()) {
            $this->error('Tidak ada outlet ditemukan.');
            return;
        }

        foreach ($outlets as $outlet) {
            $balance = OutletBalance::firstOrCreate(
                ['outlet_id' => $outlet->id],
                ['balance' => 0, 'total_withdrawn' => 0],
            );

            $balance->increment('balance', $amount);

            BalanceTransaction::create([
                'outlet_id' => $outlet->id,
                'type' => 'qris_payment',
                'amount' => $amount,
                'description' => 'Saldo awal testing',
            ]);

            $this->info("✓ Outlet {$outlet->name}: +Rp " . number_format($amount / 100, 0, ',', '.'));
        }

        $this->newLine();
        $this->info('✅ Selesai! Buka halaman Penarikan untuk test withdraw.');
    }
}
