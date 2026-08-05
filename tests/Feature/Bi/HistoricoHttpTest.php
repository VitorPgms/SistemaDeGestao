<?php

namespace Tests\Feature\Bi;

use App\Models\User;
use App\Modules\Bi\Filament\Pages\Historico;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HistoricoHttpTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    private Setor $setorBetim;

    private Setor $setorGoiania;

    private Produto $produto;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'acessar-todos-cds', 'historico.view', 'entradas.view', 'entradas.manage',
            'colaboradores.view', 'colaboradores.manage',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo(['acessar-todos-cds', 'historico.view']);
        Role::findOrCreate('supervisor', 'web')->givePermissionTo(['historico.view', 'colaboradores.view', 'colaboradores.manage']);
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo(['entradas.view', 'entradas.manage']);

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);
        $this->goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);

        $this->setorBetim = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Almoxarifado']);
        $this->setorGoiania = Setor::create(['cd_id' => $this->goiania->id, 'nome' => 'Expedição']);

        $categoria = Categoria::create(['nome' => 'EPI']);
        $this->produto = Produto::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Botina Bracol CA 12345',
            'codigo_interno' => 'BOT-001',
            'unidade' => 'PAR',
            'status' => 'ativo',
        ]);
    }

    private function userComPapel(string $role, CentroDistribuicao $cd): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => "{$role}@teste.local",
            'password' => bcrypt('password'),
            'cd_id' => $cd->id,
            'ativo' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_user_without_historico_permission_is_forbidden(): void
    {
        $almoxarife = $this->userComPapel('almoxarife', $this->betim);

        $this->actingAs($almoxarife)->get(Historico::getUrl())->assertForbidden();
    }

    public function test_supervisor_only_sees_history_from_own_cd_even_forcing_cd_id_in_url(): void
    {
        $supervisorBetim = $this->userComPapel('supervisor', $this->betim);

        Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'Colaborador Betim',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'status' => 'ativo',
        ]);

        Colaborador::create([
            'cd_id' => $this->goiania->id,
            'setor_id' => $this->setorGoiania->id,
            'nome' => 'Colaborador Goiania',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'status' => 'ativo',
        ]);

        $response = $this->actingAs($supervisorBetim)->get(Historico::getUrl(['cd_id' => $this->goiania->id]));

        $response->assertOk();
        $response->assertSee('Colaborador Betim');
        $response->assertDontSee('Colaborador Goiania');
    }

    public function test_administrador_sees_history_from_all_cds(): void
    {
        $admin = $this->userComPapel('administrador', $this->betim);

        Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'Colaborador Betim',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'status' => 'ativo',
        ]);

        Colaborador::create([
            'cd_id' => $this->goiania->id,
            'setor_id' => $this->setorGoiania->id,
            'nome' => 'Colaborador Goiania',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'status' => 'ativo',
        ]);

        $response = $this->actingAs($admin)->get(Historico::getUrl());

        $response->assertOk();
        $response->assertSee('Colaborador Betim');
        $response->assertSee('Colaborador Goiania');
    }

    public function test_global_catalog_changes_are_visible_regardless_of_cd_scope(): void
    {
        $supervisorBetim = $this->userComPapel('supervisor', $this->betim);

        $this->produto->update(['nome' => 'Botina Bracol CA 99999']);

        $response = $this->actingAs($supervisorBetim)->get(Historico::getUrl());

        $response->assertOk();
        $response->assertSee('Produto: Botina Bracol CA 99999');
    }

    public function test_cancelled_entrada_is_labeled_as_cancelado_with_reason(): void
    {
        $admin = $this->userComPapel('administrador', $this->betim);
        $almoxarife = $this->userComPapel('almoxarife', $this->betim);

        $fornecedor = Fornecedor::create(['cd_id' => $this->betim->id, 'razao_social' => 'Bracol EPIs']);
        $responsavel = ResponsavelRecebimento::create(['cd_id' => $this->betim->id, 'nome' => 'Maria Recebimento']);

        $this->actingAs($almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $fornecedor->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '10.00',
            'responsavel_recebimento_id' => $responsavel->id,
        ]);

        $entrada = Entrada::withoutGlobalScopes()->sole();

        $this->actingAs($almoxarife)->post(route('entradas.cancelar', $entrada), [
            'motivo_cancelamento' => 'Nota fiscal cancelada pelo fornecedor',
        ]);

        $response = $this->actingAs($admin)->get(Historico::getUrl(['acao' => 'cancelado']));

        $response->assertOk();
        $response->assertSee('Cancelado');
        $response->assertSee("Entrada #{$entrada->id}");
        $response->assertSee('Nota fiscal cancelada pelo fornecedor');
    }

    public function test_pagination_works_with_many_records(): void
    {
        $admin = $this->userComPapel('administrador', $this->betim);

        foreach (range(1, 25) as $i) {
            Setor::create(['cd_id' => $this->betim->id, 'nome' => "Setor {$i}"]);
        }

        $pagina1 = $this->actingAs($admin)->get(Historico::getUrl());
        $pagina2 = $this->actingAs($admin)->get(Historico::getUrl(['page' => 2]));

        $pagina1->assertOk();
        $pagina2->assertOk();
        $this->assertNotEquals($pagina1->getContent(), $pagina2->getContent());
    }
}
