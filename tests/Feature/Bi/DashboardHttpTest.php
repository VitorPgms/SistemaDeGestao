<?php

namespace Tests\Feature\Bi;

use App\Models\User;
use App\Modules\Bi\Filament\Pages\Dashboard;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Estoque;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use App\Modules\Estoque\Notifications\EstoqueMinimoAtingido;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardHttpTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private Produto $produto;

    private Fornecedor $fornecedor;

    private Colaborador $colaborador;

    private ResponsavelRecebimento $responsavel;

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

        Role::findOrCreate('almoxarife', 'web')->givePermissionTo([
            'entradas.view', 'entradas.manage', 'saidas.view', 'saidas.manage', 'estoque.view',
        ]);

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

        MotivoSaida::create(['nome' => 'Uso operacional']);

        $this->almoxarife = User::create([
            'name' => 'Almoxarife',
            'email' => 'almoxarife@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $this->almoxarife->assignRole('almoxarife');
    }

    public function test_dashboard_shows_indicators_for_the_current_month(): void
    {
        $this->travelTo(now()->setDate(2026, 7, 15));

        $this->actingAs($this->almoxarife)->post(route('entradas.store'), [
            'produto_id' => $this->produto->id,
            'fornecedor_id' => $this->fornecedor->id,
            'numero_nota_fiscal' => 'NF-001',
            'data_compra' => '2026-07-01',
            'data_entrega' => '2026-07-05',
            'quantidade' => 20,
            'valor_unitario' => '10.00',
            'responsavel_recebimento_id' => $this->responsavel->id,
        ]);

        $this->actingAs($this->almoxarife)->post(route('saidas.store'), [
            'produto_id' => $this->produto->id,
            'quantidade' => 5,
            'colaborador_id' => $this->colaborador->id,
            'liberado_por' => $this->almoxarife->id,
            'motivo_saida_id' => MotivoSaida::first()->id,
            'data' => '2026-07-10',
            'hora' => '14:30',
        ]);

        $estoque = Estoque::withoutGlobalScopes()->sole();
        $estoque->update(['quantidade_minima' => 20, 'quantidade_ideal' => 30]);

        $response = $this->actingAs($this->almoxarife)->get(Dashboard::getUrl([
            'data_inicio' => '2026-07-01',
            'data_fim' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['Itens em situação Crítica', '1']);
        $response->assertSeeInOrder(['Entradas no período', '20']);
        $response->assertSee('R$ 200,00');
        $response->assertSeeInOrder(['Saídas no período', '5']);
    }

    public function test_user_can_mark_a_low_stock_notification_as_read(): void
    {
        $estoque = Estoque::create([
            'cd_id' => $this->betim->id,
            'produto_id' => $this->produto->id,
            'quantidade_atual' => 2,
            'quantidade_minima' => 5,
        ]);

        $this->almoxarife->notify(new EstoqueMinimoAtingido($estoque));
        $notificacao = $this->almoxarife->unreadNotifications()->sole();

        $this->actingAs($this->almoxarife)
            ->post(route('dashboard.notificacoes.marcar-lida', $notificacao->id))
            ->assertRedirect();

        $this->assertSame(0, $this->almoxarife->unreadNotifications()->count());
    }
}
