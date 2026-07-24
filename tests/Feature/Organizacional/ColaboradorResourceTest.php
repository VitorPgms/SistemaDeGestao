<?php

namespace Tests\Feature\Organizacional;

use App\Models\User;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Pages\CreateColaborador;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Pages\ListColaboradores;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ColaboradorResourceTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    private Setor $setorBetim;

    private Setor $setorGoiania;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'colaboradores.view', 'colaboradores.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('supervisor', 'web')->givePermissionTo(['colaboradores.view', 'colaboradores.manage']);
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo(['colaboradores.view']);

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);
        $this->goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);

        $this->setorBetim = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Recebimento']);
        $this->setorGoiania = Setor::create(['cd_id' => $this->goiania->id, 'nome' => 'Expedição']);
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

    public function test_supervisor_only_sees_colaboradores_from_own_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);

        $colaboradorBetim = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'João',
            'funcao' => 'Estoquista',
            'data_admissao' => '2024-01-10',
            'status' => StatusColaborador::Ativo,
        ]);

        $colaboradorGoiania = Colaborador::create([
            'cd_id' => $this->goiania->id,
            'setor_id' => $this->setorGoiania->id,
            'nome' => 'Maria',
            'funcao' => 'Conferente',
            'data_admissao' => '2024-02-15',
            'status' => StatusColaborador::Ativo,
        ]);

        $this->actingAs($supervisor);

        Livewire::test(ListColaboradores::class)
            ->assertCanSeeTableRecords([$colaboradorBetim])
            ->assertCanNotSeeTableRecords([$colaboradorGoiania]);
    }

    public function test_setor_options_are_restricted_to_the_colaborador_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateColaborador::class)
            ->fillForm([
                'setor_id' => $this->setorGoiania->id,
                'nome' => 'Pedro',
                'funcao' => 'Auxiliar',
                'data_admissao' => '2024-03-01',
                'status' => StatusColaborador::Ativo->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['setor_id']);
    }

    public function test_supervisor_can_create_colaborador_in_own_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateColaborador::class)
            ->fillForm([
                'setor_id' => $this->setorBetim->id,
                'nome' => 'Ana',
                'funcao' => 'Almoxarife',
                'data_admissao' => '2024-03-01',
                'status' => StatusColaborador::Ativo->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $colaborador = Colaborador::query()->where('nome', 'Ana')->firstOrFail();

        $this->assertSame($this->betim->id, $colaborador->cd_id);
    }

    public function test_almoxarife_cannot_create_colaboradores(): void
    {
        $almoxarife = $this->userComPapel('almoxarife', $this->betim);

        $this->assertFalse($almoxarife->can('create', Colaborador::class));
    }
}
