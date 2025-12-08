<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat role
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'scanner']);
        Role::firstOrCreate(['name' => 'user']);
    }
}
