<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Papéis e permissões base do sistema.
 *
 * Cada módulo futuro é responsável por seedar suas próprias permissions
 * (ex: "produtos.criar", "entradas.criar") sem precisar alterar este
 * seeder — aqui só ficam os papéis em si e as permissions transversais.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Permission::findOrCreate('acessar-todos-cds', 'web');

        $administrador = Role::findOrCreate('administrador', 'web');
        $administrador->givePermissionTo('acessar-todos-cds');

        Role::findOrCreate('supervisor', 'web');
        Role::findOrCreate('almoxarife', 'web');
        Role::findOrCreate('consulta', 'web');
    }
}
