<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Filament\Pages\Entradas;
use App\Modules\Estoque\Filament\Pages\EstoqueLista;
use App\Modules\Estoque\Filament\Pages\NovaEntrada;
use App\Modules\Estoque\Filament\Pages\NovaSaida;
use App\Modules\Estoque\Filament\Pages\Saidas;
use App\Modules\Estoque\Enums\StatusEntrada;
use App\Modules\Estoque\Enums\StatusSaida;
use App\Modules\Estoque\Filament\Pages\EntradaEditar;
use App\Modules\Estoque\Filament\Pages\SaidaEditar;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    private ResponsavelRecebimento $responsavelBetim;

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

        $this->responsavelBetim = ResponsavelRecebimento::create(['cd_id' => $this->betim->id, 'nome' => 'Maria Recebimento']);

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
        $this->actingAs($this->almoxarife)->get(Entradas::getUrl())->assertOk();
        $this->actingAs($this->almoxarife)->get(NovaEntrada::getUrl())->assertOk();
        $this->actingAs($this->almoxarife)->get(Saidas::getUrl())->assertOk();
        $this->actingAs($this->almoxarife)->get(NovaSaida::getUrl())->assertOk();
        $this->actingAs($this->almoxarife)->get(EstoqueLista::getUrl())->assertOk();
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

        $this->actingAs($consulta)->get(NovaEntrada::getUrl())->assertForbidden();
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
                'responsavel_recebimento_id' => $this->responsavelBetim->id,
            ])
            ->assertRedirect(Entradas::getUrl());

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
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
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
            ->assertRedirect(Saidas::getUrl());

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
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
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
                'responsavel_recebimento_id' => $this->responsavelBetim->id,
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
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
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
            ->assertRedirect(EstoqueLista::getUrl());

        $estoque->refresh();
        $this->assertSame(5, $estoque->quantidade_minima);
        $this->assertSame(30, $estoque->quantidade_ideal);
        $this->assertSame('Prateleira A12', $estoque->localizacao);
    }

    public function test_full_http_flow_updates_entrada_and_recalculates_stock_and_total(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $entrada = Entrada::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)
            ->get(EntradaEditar::getUrl(['entrada' => $entrada]))
            ->assertOk();

        $this->actingAs($this->almoxarife)
            ->put(route('entradas.update', $entrada), [
                'produto_id' => $this->produto->id,
                'fornecedor_id' => $this->fornecedorBetim->id,
                'numero_nota_fiscal' => 'NF-001-B',
                'data_compra' => '2026-07-01',
                'data_entrega' => '2026-07-06',
                'quantidade' => 12,
                'valor_unitario' => '100.00',
                'responsavel_recebimento_id' => $this->responsavelBetim->id,
            ])
            ->assertRedirect(Entradas::getUrl());

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(12, $estoque->quantidade_atual);

        $entrada->refresh();
        $this->assertSame('NF-001-B', $entrada->numero_nota_fiscal);
        $this->assertEquals(1200, $entrada->valor_total);
    }

    public function test_full_http_flow_cancels_entrada_and_reverts_stock(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $entrada = Entrada::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)
            ->post(route('entradas.cancelar', $entrada), [
                'motivo_cancelamento' => 'Nota fiscal cancelada pelo fornecedor',
            ])
            ->assertRedirect(Entradas::getUrl());

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(0, $estoque->quantidade_atual);

        $entrada->refresh();
        $this->assertSame(StatusEntrada::Cancelada, $entrada->status);
        $this->assertSame('Nota fiscal cancelada pelo fornecedor', $entrada->motivo_cancelamento);
        $this->assertSame($this->almoxarife->id, $entrada->cancelado_por);
    }

    public function test_cancelling_an_entrada_requires_a_reason(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $entrada = Entrada::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)
            ->post(route('entradas.cancelar', $entrada), [])
            ->assertSessionHasErrors('motivo_cancelamento');

        $this->assertSame(StatusEntrada::Ativa, $entrada->refresh()->status);
    }

    public function test_cancelling_an_entrada_already_partially_consumed_by_a_saida_is_blocked(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $entrada = Entrada::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 15,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $response = $this->actingAs($this->almoxarife)
            ->post(route('entradas.cancelar', $entrada), [
                'motivo_cancelamento' => 'Tentativa de cancelar após consumo',
            ]);

        $this->assertNotEmpty($response->getSession()->get('filament.notifications'));

        $this->assertSame(StatusEntrada::Ativa, $entrada->refresh()->status);
        $this->assertSame(5, Estoque::withoutGlobalScopes()->sole()->quantidade_atual);
    }

    public function test_entradas_index_filters_by_numero_nota_fiscal(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-100',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 10,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-200',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-06',
            'quantidade' => 5,
            'valor_unitario' => '50.00',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $response = $this->actingAs($this->almoxarife)
            ->get(Entradas::getUrl(['numero_nota_fiscal' => 'NF-100']))
            ->assertOk();

        $response->assertSee('NF-100');
        $response->assertDontSee('NF-200');
    }

    public function test_entradas_index_hides_cancelled_entries_by_default_and_shows_details_via_status_filter(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $entrada = Entrada::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)->post(route('entradas.cancelar', $entrada), [
            'motivo_cancelamento' => 'Divergência na nota fiscal',
        ]);

        $this->actingAs($this->almoxarife)
            ->get(Entradas::getUrl())
            ->assertOk()
            ->assertDontSee('NF-001');

        $response = $this->actingAs($this->almoxarife)
            ->get(Entradas::getUrl(['status' => 'cancelada']))
            ->assertOk();

        $response->assertSee('NF-001');
        $response->assertSee('Cancelada');
        $response->assertSee('Divergência na nota fiscal');
        $response->assertSee($this->almoxarife->name);

        $this->actingAs($this->almoxarife)
            ->get(Entradas::getUrl(['status' => 'todas']))
            ->assertOk()
            ->assertSee('NF-001');
    }

    public function test_entradas_index_advanced_filters_by_categoria_fornecedor_and_responsavel(): void
    {
        $outroFornecedor = Fornecedor::create(['cd_id' => $this->betim->id, 'razao_social' => 'Outro Fornecedor']);
        $outroResponsavel = ResponsavelRecebimento::create(['cd_id' => $this->betim->id, 'nome' => 'Outro Responsável']);

        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 10,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $outroFornecedor->id,
            'numero_nota_fiscal' => 'NF-002',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 10,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $outroResponsavel->id,
        ]);

        $response = $this->actingAs($this->almoxarife)
            ->get(Entradas::getUrl(['fornecedor_id' => $this->fornecedorBetim->id]))
            ->assertOk();
        $response->assertSee('NF-001');
        $response->assertDontSee('NF-002');

        $response = $this->actingAs($this->almoxarife)
            ->get(Entradas::getUrl(['responsavel_recebimento_id' => $outroResponsavel->id]))
            ->assertOk();
        $response->assertSee('NF-002');
        $response->assertDontSee('NF-001');
    }

    public function test_nota_fiscal_attachment_is_stored_and_kept_after_editing_and_cancelling_an_entrada(): void
    {
        Storage::fake('public');

        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
            'nota_fiscal_anexo' => UploadedFile::fake()->createWithContent('nf-001.pdf', "%PDF-1.4\n%%EOF"),
        ]);

        $entrada = Entrada::withoutGlobalScopes()->sole();
        $this->assertCount(1, $entrada->getMedia(Entrada::COLECAO_NOTA_FISCAL));

        $this->actingAs($this->almoxarife)->put(route('entradas.update', $entrada), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $entrada->refresh();
        $this->assertCount(1, $entrada->getMedia(Entrada::COLECAO_NOTA_FISCAL));

        $this->actingAs($this->almoxarife)->post(route('entradas.cancelar', $entrada), [
            'motivo_cancelamento' => 'Nota fiscal cancelada pelo fornecedor',
        ]);

        $entrada->refresh();
        $this->assertCount(1, $entrada->getMedia(Entrada::COLECAO_NOTA_FISCAL));
    }

    public function test_notifications_are_sent_after_registering_updating_and_cancelling_an_entrada(): void
    {
        $storeResponse = $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 10,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);
        $this->assertNotEmpty($storeResponse->getSession()->get('filament.notifications'));

        $entrada = Entrada::withoutGlobalScopes()->sole();

        $updateResponse = $this->actingAs($this->almoxarife)->put(route('entradas.update', $entrada), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 12,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);
        $this->assertNotEmpty($updateResponse->getSession()->get('filament.notifications'));

        $cancelResponse = $this->actingAs($this->almoxarife)->post(route('entradas.cancelar', $entrada), [
            'motivo_cancelamento' => 'Motivo de teste',
        ]);
        $this->assertNotEmpty($cancelResponse->getSession()->get('filament.notifications'));
    }

    public function test_full_http_flow_updates_saida_and_recalculates_stock(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 5,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $saida = \App\Modules\Estoque\Models\Saida::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)
            ->get(SaidaEditar::getUrl(['saida' => $saida]))
            ->assertOk();

        $this->actingAs($this->almoxarife)
            ->put(route('saidas.update', $saida), [
                'produto_id' => $this->produto->id,
                'quantidade' => 8,
                'colaborador_id' => $this->colaboradorBetim->id,
                'liberado_por' => $this->almoxarife->id,
                'motivo_saida_id' => $this->motivo->id,
                'data' => '2026-07-10',
                'hora' => '15:00',
            ])
            ->assertRedirect(Saidas::getUrl());

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(12, $estoque->quantidade_atual);

        $saida->refresh();
        $this->assertSame(8, $saida->quantidade);
    }

    public function test_full_http_flow_cancels_saida_and_reverts_stock(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 5,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $saida = \App\Modules\Estoque\Models\Saida::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)
            ->post(route('saidas.cancelar', $saida), [
                'motivo_cancelamento' => 'Retirada registrada por engano',
            ])
            ->assertRedirect(Saidas::getUrl());

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $this->assertSame(20, $estoque->quantidade_atual);

        $saida->refresh();
        $this->assertSame(StatusSaida::Cancelada, $saida->status);
        $this->assertSame('Retirada registrada por engano', $saida->motivo_cancelamento);
        $this->assertSame($this->almoxarife->id, $saida->cancelado_por);
    }

    public function test_cancelling_a_saida_requires_a_reason(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 5,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $saida = \App\Modules\Estoque\Models\Saida::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)
            ->post(route('saidas.cancelar', $saida), [])
            ->assertSessionHasErrors('motivo_cancelamento');

        $this->assertSame(StatusSaida::Ativa, $saida->refresh()->status);
    }

    public function test_cancelling_an_already_cancelled_saida_is_blocked(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 5,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $saida = \App\Modules\Estoque\Models\Saida::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)->post(route('saidas.cancelar', $saida), [
            'motivo_cancelamento' => 'Primeiro cancelamento',
        ]);

        $response = $this->actingAs($this->almoxarife)->post(route('saidas.cancelar', $saida), [
            'motivo_cancelamento' => 'Segunda tentativa',
        ]);

        $this->assertNotEmpty($response->getSession()->get('filament.notifications'));
        $this->assertSame(20, Estoque::withoutGlobalScopes()->sole()->quantidade_atual);
    }

    public function test_saidas_index_hides_cancelled_entries_by_default_and_shows_details_via_status_filter(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 5,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $saida = \App\Modules\Estoque\Models\Saida::withoutGlobalScopes()->sole();

        $this->actingAs($this->almoxarife)->post(route('saidas.cancelar', $saida), [
            'motivo_cancelamento' => 'Divergência na retirada',
        ]);

        $this->actingAs($this->almoxarife)
            ->get(Saidas::getUrl())
            ->assertOk()
            ->assertDontSee('Divergência na retirada');

        $response = $this->actingAs($this->almoxarife)
            ->get(Saidas::getUrl(['status' => 'cancelada']))
            ->assertOk();

        $response->assertSee('Cancelada');
        $response->assertSee('Divergência na retirada');
        $response->assertSee($this->almoxarife->name);

        $this->actingAs($this->almoxarife)
            ->get(Saidas::getUrl(['status' => 'todas']))
            ->assertOk()
            ->assertSee($this->colaboradorBetim->nome);
    }

    public function test_saidas_index_advanced_filters_by_motivo_and_colaborador(): void
    {
        $outroMotivo = MotivoSaida::create(['nome' => 'Descarte']);
        $setor = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Produção']);
        $outroColaborador = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $setor->id,
            'nome' => 'Pedro',
            'funcao' => 'Operador',
            'data_admissao' => '2024-01-01',
            'status' => StatusColaborador::Ativo,
        ]);

        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 5,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 3,
            'colaborador_id' => $outroColaborador->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $outroMotivo->id,
            'data' => '2026-07-11',
            'hora' => '09:00',
        ]);

        // Os nomes filtrados também aparecem como <option> no dropdown "Retirado
        // por" (que sempre lista todos os colaboradores do CD, independente do
        // filtro aplicado), então a comparação usa contagem de ocorrências em vez
        // de assertSee/assertDontSee simples: 1 ocorrência = só no dropdown (linha
        // filtrada fora da tabela), 2+ ocorrências = dropdown + linha da tabela.
        $response = $this->actingAs($this->almoxarife)
            ->get(Saidas::getUrl(['motivo_saida_id' => $outroMotivo->id]))
            ->assertOk();
        $conteudo = $response->getContent();
        $this->assertGreaterThan(1, substr_count($conteudo, 'Pedro'));
        $this->assertSame(1, substr_count($conteudo, $this->colaboradorBetim->nome));

        $response = $this->actingAs($this->almoxarife)
            ->get(Saidas::getUrl(['colaborador_id' => $this->colaboradorBetim->id]))
            ->assertOk();
        $conteudo = $response->getContent();
        $this->assertGreaterThan(1, substr_count($conteudo, $this->colaboradorBetim->nome));
        $this->assertSame(1, substr_count($conteudo, 'Pedro'));
    }

    public function test_notifications_are_sent_after_updating_and_cancelling_a_saida(): void
    {
        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedorBetim->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '89.90',
            'responsavel_recebimento_id' => $this->responsavelBetim->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 5,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $saida = \App\Modules\Estoque\Models\Saida::withoutGlobalScopes()->sole();

        $updateResponse = $this->actingAs($this->almoxarife)->put(route('saidas.update', $saida), [
            'produto_id' => $this->produto->id,
            'quantidade' => 6,
            'colaborador_id' => $this->colaboradorBetim->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => $this->motivo->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);
        $this->assertNotEmpty($updateResponse->getSession()->get('filament.notifications'));

        $cancelResponse = $this->actingAs($this->almoxarife)->post(route('saidas.cancelar', $saida), [
            'motivo_cancelamento' => 'Motivo de teste',
        ]);
        $this->assertNotEmpty($cancelResponse->getSession()->get('filament.notifications'));
    }
}
