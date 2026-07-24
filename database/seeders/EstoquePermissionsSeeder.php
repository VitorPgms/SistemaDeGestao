<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions do módulo de Cadastros de Estoque (Categorias, Fornecedores, Produtos).
 *
 * Categorias e Produtos são cadastros globais (afetam todos os CDs), então
 * o "manage" fica restrito ao Administrador por padrão — mesmo critério já
 * usado para Centros de Distribuição e Usuários. Fornecedores são por CD,
 * então o Supervisor gerencia os do próprio CD, como já faz com Setores
 * e Colaboradores.
 */
class EstoquePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'categorias.view',
            'categorias.manage',
            'fornecedores.view',
            'fornecedores.manage',
            'produtos.view',
            'produtos.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('supervisor', 'web')->givePermissionTo([
            'categorias.view',
            'fornecedores.view',
            'fornecedores.manage',
            'produtos.view',
        ]);

        Role::findOrCreate('almoxarife', 'web')->givePermissionTo([
            'categorias.view',
            'fornecedores.view',
            'produtos.view',
        ]);

        Role::findOrCreate('consulta', 'web')->givePermissionTo([
            'categorias.view',
            'fornecedores.view',
            'produtos.view',
        ]);

        // categorias.manage e produtos.manage ficam restritos ao Administrador
        // por padrão — expansível depois só atribuindo a permissão a outro papel.
    }
}
