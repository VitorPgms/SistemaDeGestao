<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions do módulo Organizacional (CDs, Setores, Colaboradores, Usuários).
 *
 * Administrador já tem acesso irrestrito via Gate::before (AppServiceProvider),
 * então aqui só precisamos atribuir o que cada papel comum enxerga/gerencia.
 */
class OrganizacionalPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'centros-distribuicao.view',
            'centros-distribuicao.manage',
            'setores.view',
            'setores.manage',
            'colaboradores.view',
            'colaboradores.manage',
            'usuarios.view',
            'usuarios.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('supervisor', 'web')->givePermissionTo([
            'setores.view',
            'setores.manage',
            'colaboradores.view',
            'colaboradores.manage',
        ]);

        Role::findOrCreate('almoxarife', 'web')->givePermissionTo([
            'setores.view',
            'colaboradores.view',
        ]);

        Role::findOrCreate('consulta', 'web')->givePermissionTo([
            'setores.view',
            'colaboradores.view',
        ]);

        // centros-distribuicao.* e usuarios.* ficam restritos ao Administrador
        // por padrão — expansível depois só atribuindo a permissão a outro papel.
    }
}
