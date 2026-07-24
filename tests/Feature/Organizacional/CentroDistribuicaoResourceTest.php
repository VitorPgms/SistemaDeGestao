<?php

namespace Tests\Feature\Organizacional;

use App\Models\User;
use App\Modules\Organizacional\Filament\Resources\CentrosDistribuicao\Pages\ListCentrosDistribuicao;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CentroDistribuicaoResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'centros-distribuicao.view', 'centros-distribuicao.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('supervisor', 'web');
    }

    public function test_administrador_can_list_centros_distribuicao(): void
    {
        $betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $betim->id,
            'ativo' => true,
        ]);
        $admin->assignRole('administrador');

        $this->actingAs($admin);

        Livewire::test(ListCentrosDistribuicao::class)
            ->assertCanSeeTableRecords([$betim]);
    }

    public function test_supervisor_cannot_access_centros_distribuicao_by_default(): void
    {
        $betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);

        $supervisor = User::create([
            'name' => 'Supervisor',
            'email' => 'sup@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $betim->id,
            'ativo' => true,
        ]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);

        Livewire::test(ListCentrosDistribuicao::class)
            ->assertForbidden();
    }
}
