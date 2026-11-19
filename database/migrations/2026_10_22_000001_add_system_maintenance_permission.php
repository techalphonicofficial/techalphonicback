<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/** Admin → Maintenance. Granted to nobody by default; Super Admin passes every check (Gate::before). */
return new class extends Migration
{
    public function up(): void
    {
        Permission::findOrCreate('system.maintenance', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'system.maintenance')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
