<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Filament\Pages\EstoqueLista;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EstoqueListaHttpTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    private User $almoxarife;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'estoque.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('almoxarife', 'web')->givePermissionTo(['estoque.view']);

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);
        $this->goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);

        $this->almoxarife = User::create([
            'name' => 'Almoxarife',
            'email' => 'almoxarife@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $this->almoxarife->assignRole('almoxarife');
    }

    private function criarProduto(string $nome): Produto
    {
        $categoria = Categoria::firstOrCreate(['nome' => 'EPI']);

        return Produto::create([
            'categoria_id' => $categoria->id,
            'nome' => $nome,
            'codigo_interno' => strtoupper(str_replace(' ', '-', $nome)),
            'unidade' => 'PAR',
            'status' => 'ativo',
        ]);
    }

    // O nome do produto também aparece no <select> de filtro (lista todos os
    // produtos cadastrados, estejam ou não visíveis na tabela), então o
    // marcador usado para diferenciar "aparece na tabela" é o fornecedor
    // preferencial vinculado ao estoque, que só é renderizado na linha.
    private function criarFornecedor(int $cdId, string $razaoSocial): Fornecedor
    {
        return Fornecedor::create(['cd_id' => $cdId, 'razao_social' => $razaoSocial]);
    }

    public function test_registro_completamente_zerado_e_sem_movimentacao_fica_oculto_por_padrao(): void
    {
        $zerado = $this->criarProduto('Bota CAT Zerada');
        $critico = $this->criarProduto('Bota CAT Critica');
        $fornecedorZerado = $this->criarFornecedor($this->betim->id, 'Fornecedor Marca Zerada');
        $fornecedorCritico = $this->criarFornecedor($this->betim->id, 'Fornecedor Marca Critica');

        Estoque::create([
            'cd_id' => $this->betim->id,
            'produto_id' => $zerado->id,
            'quantidade_atual' => 0,
            'quantidade_minima' => 0,
            'quantidade_ideal' => 0,
            'fornecedor_preferencial_id' => $fornecedorZerado->id,
        ]);

        Estoque::create([
            'cd_id' => $this->betim->id,
            'produto_id' => $critico->id,
            'quantidade_atual' => 0,
            'quantidade_minima' => 21,
            'quantidade_ideal' => 25,
            'fornecedor_preferencial_id' => $fornecedorCritico->id,
        ]);

        $response = $this->actingAs($this->almoxarife)->get(EstoqueLista::getUrl());

        $response->assertOk();
        $response->assertDontSee('Fornecedor Marca Zerada');
        $response->assertSee('Fornecedor Marca Critica');
        $response->assertSee('Crítico');
    }

    public function test_filtro_mostrar_sem_estoque_exibe_os_registros_ocultados(): void
    {
        $zerado = $this->criarProduto('Bota CAT Zerada');
        $fornecedorZerado = $this->criarFornecedor($this->betim->id, 'Fornecedor Marca Zerada');

        Estoque::create([
            'cd_id' => $this->betim->id,
            'produto_id' => $zerado->id,
            'quantidade_atual' => 0,
            'quantidade_minima' => 0,
            'quantidade_ideal' => 0,
            'fornecedor_preferencial_id' => $fornecedorZerado->id,
        ]);

        $semFiltro = $this->actingAs($this->almoxarife)->get(EstoqueLista::getUrl());
        $semFiltro->assertDontSee('Fornecedor Marca Zerada');

        $comFiltro = $this->actingAs($this->almoxarife)->get(EstoqueLista::getUrl(['mostrar_sem_estoque' => 1]));
        $comFiltro->assertSee('Fornecedor Marca Zerada');
    }

    public function test_isolamento_por_cd_continua_valendo_com_o_filtro_ativo(): void
    {
        $produtoGoiania = $this->criarProduto('Bota Goiania Zerada');
        $fornecedorGoiania = $this->criarFornecedor($this->goiania->id, 'Fornecedor Marca Goiania');

        Estoque::create([
            'cd_id' => $this->goiania->id,
            'produto_id' => $produtoGoiania->id,
            'quantidade_atual' => 0,
            'quantidade_minima' => 0,
            'quantidade_ideal' => 0,
            'fornecedor_preferencial_id' => $fornecedorGoiania->id,
        ]);

        $response = $this->actingAs($this->almoxarife)->get(EstoqueLista::getUrl(['mostrar_sem_estoque' => 1]));

        $response->assertOk();
        $response->assertDontSee('Fornecedor Marca Goiania');
    }
}
