<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Filament\Resources\Fornecedores\Pages\CreateFornecedor;
use App\Modules\Estoque\Filament\Resources\Fornecedores\Pages\ListFornecedores;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FornecedorResourceTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'fornecedores.view', 'fornecedores.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('supervisor', 'web')->givePermissionTo(['fornecedores.view', 'fornecedores.manage']);

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

    public function test_supervisor_only_sees_fornecedores_from_own_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);

        $fornecedorBetim = Fornecedor::create(['cd_id' => $this->betim->id, 'razao_social' => 'Bracol EPIs']);
        $fornecedorGoiania = Fornecedor::create(['cd_id' => $this->goiania->id, 'razao_social' => 'Vonder Ferramentas']);

        $this->actingAs($supervisor);

        Livewire::test(ListFornecedores::class)
            ->assertCanSeeTableRecords([$fornecedorBetim])
            ->assertCanNotSeeTableRecords([$fornecedorGoiania]);
    }

    public function test_same_cnpj_is_allowed_in_different_cds_but_not_in_the_same_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Fornecedor::create(['cd_id' => $this->betim->id, 'razao_social' => 'Bracol EPIs', 'cnpj' => '11.111.111/0001-11']);

        Livewire::test(CreateFornecedor::class)
            ->fillForm([
                'razao_social' => 'Bracol Filial',
                'cnpj' => '11.111.111/0001-11',
            ])
            ->call('create')
            ->assertHasFormErrors(['cnpj']);
    }
}
