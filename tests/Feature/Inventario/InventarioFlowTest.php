<?php

namespace Tests\Feature\Inventario;

use App\Models\User;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Estoque\Services\EstoqueService;
use App\Modules\Inventario\Enums\StatusInventario;
use App\Modules\Inventario\Models\Inventario;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventarioFlowTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    private Produto $produto;

    private Fornecedor $fornecedor;

    private Colaborador $colaborador;

    private User $almoxarife;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'acessar-todos-cds', 'inventarios.view', 'inventarios.manage',
            'entradas.manage', 'saidas.manage',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo([
            'inventarios.view', 'inventarios.manage', 'entradas.manage', 'saidas.manage',
        ]);
        Role::findOrCreate('consulta', 'web')->givePermissionTo(['inventarios.view']);

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

        $this->fornecedor = Fornecedor::create(['cd_id' => $this->betim->id, 'razao_social' => 'Bracol EPIs']);

        $setor = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Almoxarifado']);
        $this->colaborador = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $setor->id,
            'nome' => 'João',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'status' => StatusColaborador::Ativo,
        ]);

        $this->almoxarife = User::create([
            'name' => 'Almoxarife',
            'email' => 'almoxarife@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $this->almoxarife->assignRole('almoxarife');
    }

    private function estoqueAtual(): int
    {
        return Estoque::withoutGlobalScopes()->sole()->quantidade_atual;
    }

    public function test_full_inventory_flow_adjusts_stock_downward_and_registers_a_compensating_saida(): void
    {
        $this->actingAs($this->almoxarife);

        app(EstoqueService::class)->registrarEntrada([
            'cd_id' => $this->betim->id,
            'produto_id' => $this->produto->id,
            'produto_variacao_id' => null,
            'fornecedor_id' => $this->fornecedor->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-01',
            'quantidade' => 20,
            'valor_unitario' => 89.90,
            'valor_total' => 1798.00,
            'colaborador_recebimento_id' => $this->colaborador->id,
            'registrado_por' => $this->almoxarife->id,
        ]);

        $this->assertSame(20, $this->estoqueAtual());

        // Abre o inventário: deve tirar o retrato de 20 unidades no sistema.
        $this->post(route('inventarios.store'), ['data_contagem' => '2026-07-15'])
            ->assertRedirect();

        $inventario = Inventario::withoutGlobalScopes()->sole();
        $item = $inventario->itens()->where('produto_id', $this->produto->id)->sole();
        $this->assertSame(20, $item->quantidade_sistema);

        // Contagem física encontrou só 17 (perda de 3).
        $this->put(route('inventarios.contagem', $inventario), [
            'quantidades' => [$item->id => 17],
        ])->assertRedirect();

        $this->post(route('inventarios.finalizar', $inventario))->assertRedirect();

        $inventario->refresh();
        $this->assertSame(StatusInventario::Concluido, $inventario->status);
        $this->assertSame(17, $this->estoqueAtual());

        $ajuste = Saida::withoutGlobalScopes()->sole();
        $this->assertSame(3, $ajuste->quantidade);
        $this->assertSame(Inventario::class, $ajuste->origem_type);
        $this->assertSame($inventario->id, $ajuste->origem_id);
        $this->assertNull($ajuste->colaborador_id);
    }

    public function test_positive_divergence_generates_a_compensating_entrada(): void
    {
        $this->actingAs($this->almoxarife);

        app(EstoqueService::class)->registrarEntrada([
            'cd_id' => $this->betim->id,
            'produto_id' => $this->produto->id,
            'produto_variacao_id' => null,
            'fornecedor_id' => $this->fornecedor->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-01',
            'quantidade' => 10,
            'valor_unitario' => 50,
            'valor_total' => 500,
            'colaborador_recebimento_id' => $this->colaborador->id,
            'registrado_por' => $this->almoxarife->id,
        ]);

        $this->post(route('inventarios.store'), ['data_contagem' => '2026-07-15']);
        $inventario = Inventario::withoutGlobalScopes()->sole();
        $item = $inventario->itens()->sole();

        $this->put(route('inventarios.contagem', $inventario), [
            'quantidades' => [$item->id => 14],
        ]);
        $this->post(route('inventarios.finalizar', $inventario));

        $this->assertSame(14, $this->estoqueAtual());

        $ajuste = Entrada::withoutGlobalScopes()->where('origem_id', $inventario->id)->sole();
        $this->assertSame(4, $ajuste->quantidade);
        $this->assertNull($ajuste->fornecedor_id);
    }

    public function test_finalizar_fails_when_not_all_items_were_counted(): void
    {
        $this->actingAs($this->almoxarife);

        $this->post(route('inventarios.store'), ['data_contagem' => '2026-07-15']);
        $inventario = Inventario::withoutGlobalScopes()->sole();

        $this->post(route('inventarios.finalizar', $inventario))
            ->assertRedirect()
            ->assertSessionHas('erro');

        $inventario->refresh();
        $this->assertSame(StatusInventario::EmAndamento, $inventario->status);
    }

    public function test_cancelar_does_not_touch_stock(): void
    {
        $this->actingAs($this->almoxarife);

        app(EstoqueService::class)->registrarEntrada([
            'cd_id' => $this->betim->id,
            'produto_id' => $this->produto->id,
            'produto_variacao_id' => null,
            'fornecedor_id' => $this->fornecedor->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-01',
            'quantidade' => 10,
            'valor_unitario' => 50,
            'valor_total' => 500,
            'colaborador_recebimento_id' => $this->colaborador->id,
            'registrado_por' => $this->almoxarife->id,
        ]);

        $this->post(route('inventarios.store'), ['data_contagem' => '2026-07-15']);
        $inventario = Inventario::withoutGlobalScopes()->sole();
        $item = $inventario->itens()->sole();

        $this->put(route('inventarios.contagem', $inventario), ['quantidades' => [$item->id => 999]]);
        $this->post(route('inventarios.cancelar', $inventario))->assertRedirect(route('inventarios.index'));

        $inventario->refresh();
        $this->assertSame(StatusInventario::Cancelado, $inventario->status);
        $this->assertSame(10, $this->estoqueAtual());
    }

    public function test_supervisor_from_another_cd_cannot_see_the_inventory(): void
    {
        // Criado antes de qualquer actingAs: o guard de BelongsToCd força
        // cd_id para o CD de quem estiver autenticado no momento do save,
        // então este usuário precisa existir antes de "logarmos" como almoxarife.
        $usuarioGoiania = User::create([
            'name' => 'Almoxarife Goiânia',
            'email' => 'goiania@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->goiania->id,
            'ativo' => true,
        ]);
        $usuarioGoiania->assignRole('almoxarife');

        $this->actingAs($this->almoxarife);
        $this->post(route('inventarios.store'), ['data_contagem' => '2026-07-15']);
        $inventario = Inventario::withoutGlobalScopes()->sole();

        // CdScope esconde o registro de outro CD antes mesmo da Policy rodar,
        // então a resposta correta é 404 (não revela nem que o registro existe).
        $this->actingAs($usuarioGoiania)
            ->get(route('inventarios.show', $inventario))
            ->assertNotFound();
    }
}
