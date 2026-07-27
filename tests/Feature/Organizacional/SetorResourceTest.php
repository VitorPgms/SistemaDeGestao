<?php

namespace Tests\Feature\Organizacional;

use App\Models\User;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Filament\Resources\Setores\Pages\CreateSetor;
use App\Modules\Organizacional\Filament\Resources\Setores\Pages\ListSetores;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SetorResourceTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'setores.view', 'setores.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('supervisor', 'web')->givePermissionTo(['setores.view', 'setores.manage']);
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo(['setores.view']);

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);
        $this->goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);
    }

    private function userComPapel(string $role, CentroDistribuicao $cd): User
    {
        $user = User::create([
            'name' => $role,
            'email' => "{$role}@teste.local",
            'password' => bcrypt('password'),
            'cd_id' => $cd->id,
            'ativo' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_supervisor_only_sees_setores_from_own_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);

        $setorBetim = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Recebimento']);
        $setorGoiania = Setor::create(['cd_id' => $this->goiania->id, 'nome' => 'Expedição']);

        $this->actingAs($supervisor);

        Livewire::test(ListSetores::class)
            ->assertCanSeeTableRecords([$setorBetim])
            ->assertCanNotSeeTableRecords([$setorGoiania]);
    }

    public function test_supervisor_creating_setor_is_forced_into_own_cd_even_if_tampered(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateSetor::class)
            ->fillForm([
                'nome' => 'Qualidade',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $setor = Setor::query()->where('nome', 'Qualidade')->firstOrFail();

        $this->assertSame($this->betim->id, $setor->cd_id);
    }

    public function test_administrador_can_choose_the_cd_when_creating_a_setor(): void
    {
        $admin = $this->userComPapel('administrador', $this->betim);
        $this->actingAs($admin);

        Livewire::test(CreateSetor::class)
            ->fillForm([
                'cd_id' => $this->goiania->id,
                'nome' => 'Logística',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $setor = Setor::query()->where('nome', 'Logística')->firstOrFail();

        $this->assertSame($this->goiania->id, $setor->cd_id);
    }

    public function test_almoxarife_cannot_create_setores(): void
    {
        $almoxarife = $this->userComPapel('almoxarife', $this->betim);
        $this->actingAs($almoxarife);

        $this->assertFalse($almoxarife->can('create', Setor::class));
    }

    public function test_cannot_deactivate_setor_with_active_colaboradores(): void
    {
        $setor = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Recebimento']);

        Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $setor->id,
            'nome' => 'João',
            'funcao' => 'Estoquista',
            'data_admissao' => '2024-01-10',
            'status' => StatusColaborador::Ativo,
        ]);

        $this->expectException(ValidationException::class);

        $setor->update(['ativo' => false]);
    }

    public function test_can_deactivate_setor_without_active_colaboradores(): void
    {
        $setor = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Recebimento']);

        $setor->update(['ativo' => false]);

        $this->assertFalse($setor->fresh()->ativo);
    }
}
