<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions do módulo de Operações (Entradas, Saídas, Estoque).
 *
 * Almoxarife é o papel operacional do dia a dia: registra entradas e
 * saídas. Supervisor faz o mesmo e também define os parâmetros de
 * estoque (mínimo/ideal/localização) do próprio CD. Consulta só visualiza.
 */
class OperacoesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'entradas.view',
            'entradas.manage',
            'saidas.view',
            'saidas.manage',
            'estoque.view',
            'estoque.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('supervisor', 'web')->givePermissionTo([
            'entradas.view',
            'entradas.manage',
            'saidas.view',
            'saidas.manage',
            'estoque.view',
            'estoque.manage',
        ]);

        Role::findOrCreate('almoxarife', 'web')->givePermissionTo([
            'entradas.view',
            'entradas.manage',
            'saidas.view',
            'saidas.manage',
            'estoque.view',
        ]);

        Role::findOrCreate('consulta', 'web')->givePermissionTo([
            'entradas.view',
            'saidas.view',
            'estoque.view',
        ]);
    }
}
