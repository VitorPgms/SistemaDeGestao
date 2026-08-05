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

    public function test_dashboard_is_the_home_page_after_login(): void
    {
        $response = $this->actingAs($this->almoxarife)->get('/admin');

        $response->assertOk();
        $response->assertSee('Itens em situação Crítica');
    }

    public function test_non_admin_cannot_see_another_cds_critical_stock_via_cd_id_query_param(): void
    {
        $goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);

        $produtoGoiania = Produto::create([
            'categoria_id' => $this->produto->categoria_id,
            'nome' => 'Furadeira Goiânia',
            'codigo_interno' => 'FUR-GO',
            'unidade' => 'UN',
            'status' => 'ativo',
        ]);

        Estoque::create([
            'cd_id' => $goiania->id,
            'produto_id' => $produtoGoiania->id,
            'quantidade_atual' => 1,
            'quantidade_minima' => 5,
            'quantidade_ideal' => 10,
        ]);

        // $this->almoxarife pertence só a Betim e não tem 'acessar-todos-cds';
        // mesmo forçando cd_id de Goiânia pela URL, o card e a tabela de
        // alertas não podem refletir o item crítico de outro CD.
        $response = $this->actingAs($this->almoxarife)->get(Dashboard::getUrl(['cd_id' => $goiania->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Itens em situação Crítica', '0']);
        $response->assertSee('Nenhum item crítico ou em atenção.');
    }

    public function test_administrador_can_view_another_cds_data_by_choosing_it_in_the_cd_filter(): void
    {
        $goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);

        $produtoGoiania = Produto::create([
            'categoria_id' => $this->produto->categoria_id,
            'nome' => 'Furadeira Goiânia',
            'codigo_interno' => 'FUR-GO',
            'unidade' => 'UN',
            'status' => 'ativo',
        ]);

        Estoque::create([
            'cd_id' => $goiania->id,
            'produto_id' => $produtoGoiania->id,
            'quantidade_atual' => 1,
            'quantidade_minima' => 5,
            'quantidade_ideal' => 10,
        ]);

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $admin->assignRole('administrador');

        $response = $this->actingAs($admin)->get(Dashboard::getUrl(['cd_id' => $goiania->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Itens em situação Crítica', '1']);
    }

    public function test_dashboard_shows_upcoming_periodic_exams_ordered_by_urgency(): void
    {
        $this->travelTo(now()->setDate(2026, 7, 15));

        $vencidoHaMuitoTempo = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->colaborador->setor_id,
            'nome' => 'Vencido Antigo',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'data_ultimo_exame_periodico' => '2025-01-01',
            'data_proximo_exame_periodico' => now()->subDays(45)->toDateString(),
            'status' => StatusColaborador::Ativo,
        ]);

        $vencidoRecente = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->colaborador->setor_id,
            'nome' => 'Vencido Recente',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'data_ultimo_exame_periodico' => '2025-01-01',
            'data_proximo_exame_periodico' => now()->subDays(12)->toDateString(),
            'status' => StatusColaborador::Ativo,
        ]);

        $proximoDoVencimento = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->colaborador->setor_id,
            'nome' => 'Perto De Vencer',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'data_ultimo_exame_periodico' => '2025-01-01',
            'data_proximo_exame_periodico' => now()->addDays(8)->toDateString(),
            'status' => StatusColaborador::Ativo,
        ]);

        $foraDaJanelaDeAlerta = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->colaborador->setor_id,
            'nome' => 'Exame Distante',
            'funcao' => 'Almoxarife',
            'data_admissao' => '2024-01-01',
            'data_ultimo_exame_periodico' => '2025-01-01',
            'data_proximo_exame_periodico' => now()->addDays(90)->toDateString(),
            'status' => StatusColaborador::Ativo,
        ]);

        $response = $this->actingAs($this->almoxarife)->get(Dashboard::getUrl());

        $response->assertOk();
        $response->assertSeeInOrder(['Vencido Antigo', 'Vencido Recente', 'Perto De Vencer']);
        $response->assertDontSee('Exame Distante');
        $response->assertSee('Ver mais');
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
