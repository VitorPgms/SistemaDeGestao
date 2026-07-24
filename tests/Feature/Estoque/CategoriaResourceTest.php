<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoriaResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'categorias.view', 'categorias.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo(['categorias.view']);
    }

    public function test_almoxarife_can_view_but_not_manage_categorias(): void
    {
        $cd = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);

        $almoxarife = User::create([
            'name' => 'Almoxarife',
            'email' => 'almoxarife@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $cd->id,
            'ativo' => true,
        ]);
        $almoxarife->assignRole('almoxarife');

        $categoria = Categoria::create(['nome' => 'EPI']);

        $this->assertTrue($almoxarife->can('viewAny', Categoria::class));
        $this->assertFalse($almoxarife->can('create', Categoria::class));
        $this->assertFalse($almoxarife->can('update', $categoria));
    }
}
