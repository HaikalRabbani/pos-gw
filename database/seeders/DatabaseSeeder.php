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
        // 2. Outlets (3 cabang)
        // ──────────────────────────────────────────────
        $pusat  = Outlet::factory()->create([
            'tenant_id' => $tenant->id,
            'name'      => 'Outlet Pusat',
            'address'   => 'Jl. Merdeka No. 1, Jakarta',
            'phone'     => '021-12345678',
        ]);

        $cabang1 = Outlet::factory()->create([
            'tenant_id' => $tenant->id,
            'name'      => 'Outlet Cabang 1',
            'address'   => 'Jl. Sudirman No. 45, Bandung',
            'phone'     => '022-87654321',
        ]);

        $cabang2 = Outlet::factory()->create([
            'tenant_id' => $tenant->id,
            'name'      => 'Outlet Cabang 2',
            'address'   => 'Jl. Diponegoro No. 10, Surabaya',
            'phone'     => '031-55556666',
        ]);

        // ──────────────────────────────────────────────
        // 3. Users
        // ──────────────────────────────────────────────

        /** ── Developer (super admin) ── */
        $dev = User::factory()->create([
            'name'       => 'Developer',
            'email'      => 'dev@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
        ]);
        // Developer punya akses ke semua outlet
        $dev->outlets()->attach($pusat->id,  ['role' => 'developer']);
        $dev->outlets()->attach($cabang1->id, ['role' => 'developer']);
        $dev->outlets()->attach($cabang2->id, ['role' => 'developer']);

        /** ── Owner (pemilik bisnis) ── */
        $owner = User::factory()->create([
            'name'       => 'Owner',
            'email'      => 'admin@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '123456',
        ]);
        // Owner akses ke semua outlet
        $owner->outlets()->attach($pusat->id,  ['role' => 'admin']);
        $owner->outlets()->attach($cabang1->id, ['role' => 'admin']);
        $owner->outlets()->attach($cabang2->id, ['role' => 'admin']);

        /** ── Manager Outlet Pusat ── */
        $mgr1 = User::factory()->create([
            'name'       => 'Manager Pusat',
            'email'      => 'manager@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '111111',
        ]);
        $mgr1->outlets()->attach($pusat->id, ['role' => 'manager']);

        /** ── Manager Outlet Cabang 1 ── */
        $mgr2 = User::factory()->create([
            'name'       => 'Manager Cabang 1',
            'email'      => 'manager2@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '222222',
        ]);
        $mgr2->outlets()->attach($cabang1->id, ['role' => 'manager']);

        /** ── Kasir Outlet Pusat ── */
        $kasir1 = User::factory()->create([
            'name'       => 'Kasir Pusat',
            'email'      => 'kasir@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '333333',
        ]);
        $kasir1->outlets()->attach($pusat->id, ['role' => 'cashier']);

        /** ── Kasir Outlet Cabang 1 ── */
        $kasir2 = User::factory()->create([
            'name'       => 'Kasir Cabang 1',
            'email'      => 'kasir2@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '444444',
        ]);
        $kasir2->outlets()->attach($cabang1->id, ['role' => 'cashier']);

        /** ── Dapur Outlet Pusat ── */
        $dapur = User::factory()->create([
            'name'       => 'Koki Pusat',
            'email'      => 'dapur@pos.com',
            'password'   => bcrypt('password'),
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'pin'        => '555555',
        ]);
        $dapur->outlets()->attach($pusat->id, ['role' => 'kitchen']);

        $this->command->info('✅ Seeder selesai!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  Developer : dev@pos.com / password');
        $this->command->info('  Owner     : admin@pos.com / password');
        $this->command->info('  Manager   : manager@pos.com / password');
        $this->command->info('  Manager2  : manager2@pos.com / password');
        $this->command->info('  Kasir     : kasir@pos.com / password');
        $this->command->info('  Kasir2    : kasir2@pos.com / password');
        $this->command->info('  Koki      : dapur@pos.com / password');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
