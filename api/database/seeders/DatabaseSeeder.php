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
        $tenant = Tenant::create(['name' => 'My Business']);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@pos.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
        ]);

        $outlet = Outlet::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet',
            'address' => 'Jl. Example No. 1',
            'token_public' => bin2hex(random_bytes(32)),
        ]);

        $admin->outlets()->attach($outlet->id, ['role' => 'admin']);
    }
}
