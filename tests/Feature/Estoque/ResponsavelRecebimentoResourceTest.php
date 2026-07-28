<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Filament\Pages\NovaEntrada;
use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Pages\CreateResponsavelRecebimento;
use App\Modules\Estoque\Filament\Resources\ResponsaveisRecebimento\Pages\ListResponsaveisRecebimento;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResponsavelRecebimentoResourceTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'responsaveis-recebimento.view', 'responsaveis-recebimento.manage', 'entradas.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('supervisor', 'web')->givePermissionTo([
            'responsaveis-recebimento.view', 'responsaveis-recebimento.manage', 'entradas.manage',
        ]);

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

    public function test_supervisor_only_sees_responsaveis_from_own_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);

        $responsavelBetim = ResponsavelRecebimento::create(['cd_id' => $this->betim->id, 'nome' => 'Maria']);
        $responsavelGoiania = ResponsavelRecebimento::create(['cd_id' => $this->goiania->id, 'nome' => 'João']);

        $this->actingAs($supervisor);

        Livewire::test(ListResponsaveisRecebimento::class)
            ->assertCanSeeTableRecords([$responsavelBetim])
            ->assertCanNotSeeTableRecords([$responsavelGoiania]);
    }

    public function test_supervisor_can_create_a_responsavel_for_their_own_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateResponsavelRecebimento::class)
            ->fillForm([
                'nome' => 'Maria Recebimento',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $responsavel = ResponsavelRecebimento::withoutGlobalScopes()->sole();
        $this->assertSame($this->betim->id, $responsavel->cd_id);
        $this->assertTrue($responsavel->ativo);
    }

    public function test_inactive_responsavel_is_not_offered_when_creating_an_entrada(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);

        $ativo = ResponsavelRecebimento::create(['cd_id' => $this->betim->id, 'nome' => 'Responsável Ativo', 'ativo' => true]);
        $inativo = ResponsavelRecebimento::create(['cd_id' => $this->betim->id, 'nome' => 'Responsável Inativo', 'ativo' => false]);

        $response = $this->actingAs($supervisor)->get(NovaEntrada::getUrl())->assertOk();

        $response->assertSee($ativo->nome);
        $response->assertDontSee($inativo->nome);
    }
}
