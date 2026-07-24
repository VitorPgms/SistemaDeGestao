<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EntradaSaidaHttpTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    private Produto $produto;

    private Fornecedor $fornecedorBetim;

    private Colaborador $colaboradorBetim;

    private MotivoSaida $motivo;

    private User $almoxarife;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'acessar-todos-cds', 'entradas.view', 'entradas.manage',
            'saidas.view', 'saidas.manage', 'estoque.view', 'estoque.manage',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo([
            'entradas.view', 'entradas.manage', 'saidas.view', 'saidas.manage', 'estoque.view',
        ]);
        Role::findOrCreate('consulta', 'web')->givePermissionTo(['entradas.view', 'saidas.view', 'estoque.view']);

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);
        $this->goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);

        $categoria = Categoria::create(['nome' => 'EPI']);
        $this->produto = Produto::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Botina Bracol CA 12345',
            'codigo_interno' => 'BOT-001',
            'unidade' => 'PAR',
            'status' => 'ativo',
        ]);

        $this->fornecedorBetim = Fornecedor::create(['cd_id' => $this->betim->id, 'razao_social' => 'Bracol EPIs']);

        $setor = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Almoxarifado']);
        $this->colaboradorBetim = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $setor->id,
            'nome' => 'João',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'status' => StatusColaborador::Ativo,
        ]);

        $this->motivo = MotivoSaida::create(['nome' => 'Uso operacional']);

        $this->almoxarife = User::create([
            'name' => 'Almoxarife',
            'email' => 'almoxarife@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $this->almoxarife->assignRole('almoxarife');
    }

    public function test_almoxarife_can_view_the_entradas_and_saidas_pages(): void
    {
        $this->actingAs($this->almoxarife)->get(route('entradas.index'))->assertOk();
        $this->actingAs($this->almoxarife)->get(route('entradas.create'))->assertOk();
        $this->actingAs($this->almoxarife)->get(route('saidas.index'))->assertOk();
        $this->actingAs($this->almoxarife)->get(route('saidas.create'))->assertOk();
        $this->actingAs($this->almoxarife)->get(route('estoque.index'))->assertOk();
    }

    public function test_consulta_cannot_reach_the_entrada_creation_form(): void
    {
        $consulta = User::create([
            'name' => 'Consulta',
            'email' => 'consulta@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $consulta->assignRole('consulta');

        $this->actingAs($consulta)->get(route('entradas.create'))->assertForbidden();
    }

    public function test_full_http_flow_creates_entrada_and_updates_stock(): void
    {
        $this->actingAs($this->almoxarife)
            ->post(route('entradas.store'), [
                'produto_id' => $this->produto->id,
                'fornecedor_id' => $this->fornecedorBetim->id,
                'numero_nota_fiscal' => 'NF-001',
                'data_compra' => '2026-07-01',
                'data_entrega' => '2026-07-05',
                'quantidade' => 20,
                'valor_unitario' => '89.90',
                'colaborador_recebimento_id' => $this->colaboradorBetim->id,
            ])
            ->assertRedirect(route('entradas.index'));

        $estoque = Estoque::withoutGlobalScopes()->sole();

        $this->assertSame(20, $estoque->quantidade_atual);
        $this->assertSame($this->betim->id, $estoque->cd_id);
    }

    public function test_full_http_flow_creates_saida_and_decrements_stock(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'colaborador_recebimento_id' => $this->colaboradorBetim->id,
        ]);

        $this->actingAs($this->almoxarife)
            ->post(route('saidas.store'), [
                'produto_id' => $this->produto->id,
                'quantidade' => 5,
                'colaborador_id' => $this->colaboradorBetim->id,
                'liberado_por' => $this->almoxarife->id,
                'motivo_saida_id' => $this->motivo->id,
                'data' => '2026-07-10',
                'hora' => '14:30',
            ])
            ->assertRedirect(route('saidas.index'));

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(15, $estoque->quantidade_atual);

        $saida = \App\Modules\Estoque\Models\Saida::withoutGlobalScopes()->sole();
        $this->assertSame(StatusColaborador::Ativo, $saida->status_colaborador_snapshot);
    }

    public function test_saida_with_insufficient_stock_is_rejected_with_a_validation_error(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 3,
            'valor_unitario' => '89.90',
            'colaborador_recebimento_id' => $this->colaboradorBetim->id,
        ]);

        $this->actingAs($this->almoxarife)
            ->post(route('saidas.store'), [
                'produto_id' => $this->produto->id,
                'quantidade' => 10,
                'colaborador_id' => $this->colaboradorBetim->id,
                'liberado_por' => $this->almoxarife->id,
                'motivo_saida_id' => $this->motivo->id,
                'data' => '2026-07-10',
                'hora' => '14:30',
            ])
            ->assertSessionHasErrors('quantidade');

        $this->assertSame(3, Estoque::withoutGlobalScopes()->sole()->quantidade_atual);
    }

    public function test_entrada_cannot_use_a_fornecedor_from_another_cd(): void
    {
        $fornecedorGoiania = Fornecedor::create(['cd_id' => $this->goiania->id, 'razao_social' => 'Vonder Ferramentas']);

        $this->actingAs($this->almoxarife)
            ->post(route('entradas.store'), [
                'produto_id' => $this->produto->id,
                'fornecedor_id' => $fornecedorGoiania->id,
                'numero_nota_fiscal' => 'NF-001',
                'data_compra' => '2026-07-01',
                'data_entrega' => '2026-07-05',
                'quantidade' => 10,
                'valor_unitario' => '89.90',
                'colaborador_recebimento_id' => $this->colaboradorBetim->id,
            ])
            ->assertSessionHasErrors('fornecedor_id');
    }

    public function test_estoque_parameters_can_be_edited(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'colaborador_recebimento_id' => $this->colaboradorBetim->id,
        ]);

        $estoque = Estoque::withoutGlobalScopes()->sole();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $admin->assignRole('administrador');

        $this->actingAs($admin)
            ->put(route('estoque.update', $estoque), [
                'quantidade_minima' => 5,
                'quantidade_ideal' => 30,
                'localizacao' => 'Prateleira A12',
            ])
            ->assertRedirect(route('estoque.index'));

        $estoque->refresh();
        $this->assertSame(5, $estoque->quantidade_minima);
        $this->assertSame(30, $estoque->quantidade_ideal);
        $this->assertSame('Prateleira A12', $estoque->localizacao);
    }
}
