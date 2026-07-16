<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ──────────────────────────────────────────────
        // 1. Tenant
        // ──────────────────────────────────────────────
        $tenant = Tenant::create(['name' => 'POS Business']);

        // ──────────────────────────────────────────────
        // 2. Outlets (2 outlet)
        // ──────────────────────────────────────────────
        $outlet1 = Outlet::create([
            'tenant_id'    => $tenant->id,
            'name'         => 'Outlet Pusat',
            'address'      => 'Jl. Merdeka No. 1, Jakarta',
            'phone'        => '021-12345678',
            'is_active'    => true,
        ]);

        $outlet2 = Outlet::create([
            'tenant_id'    => $tenant->id,
            'name'         => 'Outlet Cabang',
            'address'      => 'Jl. Sudirman No. 45, Bandung',
            'phone'        => '022-87654321',
            'is_active'    => true,
        ]);

        // ──────────────────────────────────────────────
        // 3. Users — hanya 4 akun
        // ──────────────────────────────────────────────

        /** ── Developer (super admin) ── */
        $dev = User::create([
            'name'       => 'Developer',
            'email'      => 'dev@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
        ]);
        $dev->outlets()->attach($outlet1->id, ['role' => 'developer']);
        $dev->outlets()->attach($outlet2->id, ['role' => 'developer']);

        /** ── Owner (pemilik bisnis) ── */
        $owner = User::create([
            'name'       => 'Owner',
            'email'      => 'admin@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '123456',
        ]);
        $owner->outlets()->attach($outlet1->id, ['role' => 'admin']);
        $owner->outlets()->attach($outlet2->id, ['role' => 'admin']);

        /** ── Karyawan 1: Manager Outlet Pusat ── */
        $mgr = User::create([
            'name'       => 'Manager',
            'email'      => 'manager@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '111111',
        ]);
        $mgr->outlets()->attach($outlet1->id, ['role' => 'manager']);

        /** ── Karyawan 2: Kasir Outlet Pusat ── */
        $kasir = User::create([
            'name'       => 'Kasir',
            'email'      => 'kasir@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '333333',
        ]);
        $kasir->outlets()->attach($outlet1->id, ['role' => 'cashier']);

        // ──────────────────────────────────────────────
        // 4. Transaction Seeder (dummy data)
        // ──────────────────────────────────────────────
        if (app()->environment('local', 'testing')) {
            $this->call(TransactionSeeder::class);
        }

        $this->command->info('✅ Seeder selesai!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  Developer : dev@pos.com / password');
        $this->command->info('  Owner     : admin@pos.com / password');
        $this->command->info('  Manager   : manager@pos.com / password');
        $this->command->info('  Kasir     : kasir@pos.com / password');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
