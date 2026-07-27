<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InventarioPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['inventarios.view', 'inventarios.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('supervisor', 'web')->givePermissionTo(['inventarios.view', 'inventarios.manage']);
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo(['inventarios.view', 'inventarios.manage']);
        Role::findOrCreate('consulta', 'web')->givePermissionTo(['inventarios.view']);
    }
}
