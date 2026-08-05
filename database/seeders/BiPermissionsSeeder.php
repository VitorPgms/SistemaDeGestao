<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions do módulo BI (Dashboard já é acessível a qualquer usuário
 * autenticado; o Histórico/Auditoria expõe quem fez cada alteração e
 * motivos de cancelamento, então fica restrito a papéis gerenciais).
 */
class BiPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Permission::findOrCreate('historico.view', 'web');

        Role::findOrCreate('administrador', 'web')->givePermissionTo('historico.view');
        Role::findOrCreate('supervisor', 'web')->givePermissionTo('historico.view');
    }
}
