<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Exceptions\EstoqueInsuficienteException;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Enums\StatusEntrada;
use App\Modules\Estoque\Exceptions\EntradaCanceladaException;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
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

    private ResponsavelRecebimento $responsavel;

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

        $this->responsavel = ResponsavelRecebimento::create(['cd_id' => $this->betim->id, 'nome' => 'Maria Recebimento']);

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
            'responsavel_recebimento_id' => $this->responsavel->id,
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

    public function test_atualizar_entrada_recalculates_stock_by_the_quantity_delta(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $entrada = $service->registrarEntrada($this->dadosEntrada(10));

        $service->atualizarEntrada($entrada, [
            ...$this->dadosEntrada(18),
            'valor_total' => 18 * 89.90,
        ]);

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(18, $estoque->quantidade_atual);

        $entrada->refresh();
        $this->assertSame(18, $entrada->quantidade);
    }

    public function test_atualizar_entrada_blocks_when_a_later_saida_already_consumed_the_stock(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $entrada = $service->registrarEntrada($this->dadosEntrada(10));
        $service->registrarSaida($this->dadosSaida(8));

        $this->expectException(EstoqueInsuficienteException::class);

        $service->atualizarEntrada($entrada, $this->dadosEntrada(5));
    }

    /**
     * Regressão: antes, editar uma Entrada sempre revertia a quantidade
     * ANTIGA inteira antes de aplicar a nova, então qualquer edição (mesmo
     * sem mexer na quantidade, ou aumentando-a) falhava se uma Saída
     * qualquer já tivesse consumido estoque suficiente para deixar o total
     * abaixo da quantidade antiga desta Entrada. A correção aplica só a
     * diferença quando produto/variação não mudam.
     */
    public function test_atualizar_entrada_with_unchanged_quantity_succeeds_even_after_partial_consumption(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $entrada = $service->registrarEntrada($this->dadosEntrada(100));
        $service->registrarSaida($this->dadosSaida(2));

        $service->atualizarEntrada($entrada, [
            ...$this->dadosEntrada(100),
            'observacoes' => 'Nota fiscal anexada',
        ]);

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(98, $estoque->quantidade_atual);
    }

    public function test_atualizar_entrada_increasing_quantity_succeeds_even_after_partial_consumption(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $entrada = $service->registrarEntrada($this->dadosEntrada(100));
        $service->registrarSaida($this->dadosSaida(2));

        $service->atualizarEntrada($entrada, [
            ...$this->dadosEntrada(105),
            'valor_total' => 105 * 89.90,
        ]);

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(103, $estoque->quantidade_atual);
    }

    public function test_atualizar_entrada_rejects_an_already_cancelled_entrada(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $entrada = $service->registrarEntrada($this->dadosEntrada(10));
        $service->cancelarEntrada($entrada, $this->almoxarife->id, 'Nota fiscal cancelada');

        $this->expectException(EntradaCanceladaException::class);

        $service->atualizarEntrada($entrada, $this->dadosEntrada(5));
    }

    public function test_cancelar_entrada_reverts_stock_and_records_who_and_why(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $entrada = $service->registrarEntrada($this->dadosEntrada(10));

        $service->cancelarEntrada($entrada, $this->almoxarife->id, 'Divergência na nota fiscal');

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(0, $estoque->quantidade_atual);

        $entrada->refresh();
        $this->assertSame(StatusEntrada::Cancelada, $entrada->status);
        $this->assertSame('Divergência na nota fiscal', $entrada->motivo_cancelamento);
        $this->assertSame($this->almoxarife->id, $entrada->cancelado_por);
        $this->assertNotNull($entrada->cancelado_em);
        $this->assertTrue($entrada->cancelado_em->isToday());
    }

    public function test_cancelar_entrada_blocks_when_a_later_saida_already_consumed_the_stock(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $entrada = $service->registrarEntrada($this->dadosEntrada(10));
        $service->registrarSaida($this->dadosSaida(7));

        $this->expectException(EstoqueInsuficienteException::class);

        $service->cancelarEntrada($entrada, $this->almoxarife->id, 'Motivo qualquer');

        $this->assertSame(3, Estoque::withoutGlobalScopes()->sole()->quantidade_atual);
    }

    public function test_cancelar_entrada_twice_is_rejected(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        $entrada = $service->registrarEntrada($this->dadosEntrada(10));
        $service->cancelarEntrada($entrada, $this->almoxarife->id, 'Primeiro cancelamento');

        $this->expectException(EntradaCanceladaException::class);

        $service->cancelarEntrada($entrada, $this->almoxarife->id, 'Segundo cancelamento');
    }

    public function test_anexar_totais_do_periodo_sums_only_active_movements_excluding_adjustments_and_outside_period(): void
    {
        $this->actingAs($this->almoxarife);
        $service = app(EstoqueService::class);

        // Entrada ativa dentro do período: entra na soma.
        $service->registrarEntrada($this->dadosEntrada(10));

        // Entrada ativa fora do período: não deve entrar na soma.
        $service->registrarEntrada([
            ...$this->dadosEntrada(999),
            'data_compra' => '2026-01-01',
            'data_entrega' => '2026-01-02',
        ]);

        // Saída ativa dentro do período: entra na soma.
        $service->registrarSaida($this->dadosSaida(4));

        // Entrada cancelada dentro do período: não deve entrar na soma.
        $entradaCancelada = $service->registrarEntrada($this->dadosEntrada(7));
        $service->cancelarEntrada($entradaCancelada, $this->almoxarife->id, 'Motivo de teste');

        // Ajuste de inventário (origem_type preenchido): não deve entrar na soma.
        \App\Modules\Estoque\Models\Entrada::create([
            ...$this->dadosEntrada(50),
            'fornecedor_id' => null,
            'numero_nota_fiscal' => null,
            'responsavel_recebimento_id' => null,
            'origem_type' => 'ajuste-inventario-teste',
            'origem_id' => 1,
        ]);

        $estoque = Estoque::withoutGlobalScopes()->sole();

        $service->anexarTotaisDoPeriodo([$estoque], '2026-07-01', '2026-07-31');

        $this->assertSame(10, $estoque->total_entradas_periodo);
        $this->assertSame(4, $estoque->total_saidas_periodo);
    }
}
