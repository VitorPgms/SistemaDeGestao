<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Exceptions\EstoqueInsuficienteException;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Notifications\EstoqueMinimoAtingido;
use App\Modules\Estoque\Services\EstoqueService;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EstoqueServiceTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private Produto $produto;

    private Fornecedor $fornecedor;

    private Colaborador $colaborador;

    private MotivoSaida $motivo;

    private User $almoxarife;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'entradas.manage', 'saidas.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo(['entradas.manage', 'saidas.manage']);

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);

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

    private function dadosEntrada(int $quantidade = 10): array
    {
        return [
            'cd_id' => $this->betim->id,
            'produto_id' => $this->produto->id,
            'produto_variacao_id' => null,
            'fornecedor_id' => $this->fornecedor->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => $quantidade,
            'valor_unitario' => 89.90,
            'valor_total' => 89.90 * $quantidade,
            'colaborador_recebimento_id' => $this->colaborador->id,
            'registrado_por' => $this->almoxarife->id,
        ];
    }

    private function dadosSaida(int $quantidade): array
    {
        return [
            'cd_id' => $this->betim->id,
            'produto_id' => $this->produto->id,
            'produto_variacao_id' => null,
            'quantidade' => $quantidade,
            'colaborador_id' => $this->colaborador->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'status_colaborador_snapshot' => StatusColaborador::Ativo,
            'data' => '2026-07-10',
            'hora' => '14:30',
            'registrado_por' => $this->almoxarife->id,
        ];
    }

    public function test_registrar_entrada_creates_stock_row_and_increments_quantity(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $service->registrarEntrada($this->dadosEntrada(10));
        $service->registrarEntrada($this->dadosEntrada(5));

        $estoque = Estoque::withoutGlobalScopes()->sole();

        $this->assertSame(15, $estoque->quantidade_atual);
        $this->assertNotNull($estoque->ultima_entrada_at);
    }

    public function test_registrar_saida_decrements_quantity(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $service->registrarEntrada($this->dadosEntrada(10));
        $service->registrarSaida($this->dadosSaida(4));

        $estoque = Estoque::withoutGlobalScopes()->sole();

        $this->assertSame(6, $estoque->quantidade_atual);
        $this->assertNotNull($estoque->ultima_saida_at);
    }

    public function test_registrar_saida_throws_when_stock_is_insufficient(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $service->registrarEntrada($this->dadosEntrada(3));

        $this->expectException(EstoqueInsuficienteException::class);

        $service->registrarSaida($this->dadosSaida(5));

        $this->assertSame(3, Estoque::withoutGlobalScopes()->sole()->quantidade_atual);
    }

    public function test_saida_that_drops_stock_below_minimum_notifies_operational_users(): void
    {
        Notification::fake();

        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $service->registrarEntrada($this->dadosEntrada(10));

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $estoque->update(['quantidade_minima' => 5, 'quantidade_ideal' => 10]);

        $service->registrarSaida($this->dadosSaida(6));

        Notification::assertSentTo($this->almoxarife, EstoqueMinimoAtingido::class);
    }

    public function test_saida_does_not_renotify_while_already_critical(): void
    {
        Notification::fake();

        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $service->registrarEntrada($this->dadosEntrada(10));

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $estoque->update(['quantidade_minima' => 8, 'quantidade_ideal' => 10]);

        // 10 -> 8: cruza para Crítico (atual <= mínimo) e deve notificar.
        $service->registrarSaida($this->dadosSaida(2));
        Notification::assertSentTo($this->almoxarife, EstoqueMinimoAtingido::class);

        // 8 -> 7: já estava Crítico, não deve notificar de novo.
        Notification::fake();
        $service->registrarSaida($this->dadosSaida(1));
        Notification::assertNothingSent();
    }
}
