<?php

namespace Tests\Feature\Organizacional;

use App\Models\User;
use App\Modules\Estoque\Enums\StatusSaida;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\MotivoSaida;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Organizacional\Enums\StatusColaborador;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\ColaboradorResource;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Pages\CreateColaborador;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Pages\EditColaborador;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\Pages\ListColaboradores;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\RelationManagers\AtividadesRelationManager;
use App\Modules\Organizacional\Filament\Resources\Colaboradores\RelationManagers\SaidasRelationManager;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Organizacional\Models\Colaborador;
use App\Modules\Organizacional\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ColaboradorResourceTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    private Setor $setorBetim;

    private Setor $setorGoiania;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'colaboradores.view', 'colaboradores.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('supervisor', 'web')->givePermissionTo(['colaboradores.view', 'colaboradores.manage']);
        Role::findOrCreate('almoxarife', 'web')->givePermissionTo(['colaboradores.view']);

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);
        $this->goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);

        $this->setorBetim = Setor::create(['cd_id' => $this->betim->id, 'nome' => 'Recebimento']);
        $this->setorGoiania = Setor::create(['cd_id' => $this->goiania->id, 'nome' => 'Expedição']);
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

    public function test_supervisor_only_sees_colaboradores_from_own_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);

        $colaboradorBetim = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'João',
            'funcao' => 'Estoquista',
            'data_admissao' => '2024-01-10',
            'status' => StatusColaborador::Ativo,
        ]);

        $colaboradorGoiania = Colaborador::create([
            'cd_id' => $this->goiania->id,
            'setor_id' => $this->setorGoiania->id,
            'nome' => 'Maria',
            'funcao' => 'Conferente',
            'data_admissao' => '2024-02-15',
            'status' => StatusColaborador::Ativo,
        ]);

        $this->actingAs($supervisor);

        Livewire::test(ListColaboradores::class)
            ->assertCanSeeTableRecords([$colaboradorBetim])
            ->assertCanNotSeeTableRecords([$colaboradorGoiania]);
    }

    public function test_setor_options_are_restricted_to_the_colaborador_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateColaborador::class)
            ->fillForm([
                'setor_id' => $this->setorGoiania->id,
                'nome' => 'Pedro',
                'funcao' => 'Auxiliar',
                'data_admissao' => '2024-03-01',
                'status' => StatusColaborador::Ativo->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['setor_id']);
    }

    public function test_supervisor_can_create_colaborador_in_own_cd(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateColaborador::class)
            ->fillForm([
                'setor_id' => $this->setorBetim->id,
                'nome' => 'Ana',
                'funcao' => 'Almoxarife',
                'data_admissao' => '2024-03-01',
                'status' => StatusColaborador::Ativo->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $colaborador = Colaborador::query()->where('nome', 'Ana')->firstOrFail();

        $this->assertSame($this->betim->id, $colaborador->cd_id);
    }

    public function test_almoxarife_cannot_create_colaboradores(): void
    {
        $almoxarife = $this->userComPapel('almoxarife', $this->betim);

        $this->assertFalse($almoxarife->can('create', Colaborador::class));
    }

    public function test_form_rejects_data_demissao_when_status_is_ativo(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateColaborador::class)
            ->fillForm([
                'setor_id' => $this->setorBetim->id,
                'nome' => 'Carlos',
                'funcao' => 'Auxiliar',
                'data_admissao' => '2024-01-10',
                'data_demissao' => '2024-06-01',
                'status' => StatusColaborador::Ativo->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['data_demissao']);
    }

    public function test_form_requires_data_demissao_when_status_is_inativo(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateColaborador::class)
            ->fillForm([
                'setor_id' => $this->setorBetim->id,
                'nome' => 'Carlos',
                'funcao' => 'Auxiliar',
                'data_admissao' => '2024-01-10',
                'status' => StatusColaborador::Inativo->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['data_demissao']);
    }

    public function test_model_guard_rejects_data_demissao_when_ativo(): void
    {
        $this->expectException(ValidationException::class);

        Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'Carlos',
            'funcao' => 'Auxiliar',
            'data_admissao' => '2024-01-10',
            'data_demissao' => '2024-06-01',
            'status' => StatusColaborador::Ativo,
        ]);
    }

    public function test_model_guard_requires_data_demissao_when_inativo(): void
    {
        $this->expectException(ValidationException::class);

        Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'Carlos',
            'funcao' => 'Auxiliar',
            'data_admissao' => '2024-01-10',
            'status' => StatusColaborador::Inativo,
        ]);
    }

    public function test_model_guard_rejects_setor_from_different_cd(): void
    {
        $this->expectException(ValidationException::class);

        Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorGoiania->id,
            'nome' => 'Carlos',
            'funcao' => 'Auxiliar',
            'data_admissao' => '2024-01-10',
            'status' => StatusColaborador::Ativo,
        ]);
    }

    private function criarColaborador(?string $dataProximoExame): Colaborador
    {
        return Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'Carlos',
            'funcao' => 'Auxiliar',
            'data_admissao' => '2024-01-10',
            'status' => StatusColaborador::Ativo,
            'data_proximo_exame_periodico' => $dataProximoExame,
        ]);
    }

    public function test_status_exame_periodico_is_null_when_no_date_is_set(): void
    {
        $colaborador = $this->criarColaborador(null);

        $this->assertNull($colaborador->statusExamePeriodico());
    }

    public function test_status_exame_periodico_is_vencido_when_date_is_past(): void
    {
        $colaborador = $this->criarColaborador(now()->subDay()->toDateString());

        $this->assertSame('vencido', $colaborador->statusExamePeriodico());
    }

    public function test_status_exame_periodico_is_proximo_within_alert_window(): void
    {
        $colaborador = $this->criarColaborador(now()->addDays(Colaborador::DIAS_ALERTA_EXAME_PERIODICO)->toDateString());

        $this->assertSame('proximo', $colaborador->statusExamePeriodico());
    }

    public function test_status_exame_periodico_is_normal_outside_alert_window(): void
    {
        $colaborador = $this->criarColaborador(now()->addDays(Colaborador::DIAS_ALERTA_EXAME_PERIODICO + 1)->toDateString());

        $this->assertSame('normal', $colaborador->statusExamePeriodico());
    }

    public function test_exame_periodico_filter_shows_only_vencido_or_proximo(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        $vencido = $this->criarColaborador(now()->subDays(10)->toDateString());
        $proximo = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'Beatriz',
            'funcao' => 'Auxiliar',
            'data_admissao' => '2024-01-10',
            'status' => StatusColaborador::Ativo,
            'data_proximo_exame_periodico' => now()->addDays(5)->toDateString(),
        ]);
        $distante = Colaborador::create([
            'cd_id' => $this->betim->id,
            'setor_id' => $this->setorBetim->id,
            'nome' => 'Diego',
            'funcao' => 'Auxiliar',
            'data_admissao' => '2024-01-10',
            'status' => StatusColaborador::Ativo,
            'data_proximo_exame_periodico' => now()->addDays(90)->toDateString(),
        ]);

        Livewire::test(ListColaboradores::class)
            ->filterTable('exame_periodico', true)
            ->assertCanSeeTableRecords([$vencido, $proximo])
            ->assertCanNotSeeTableRecords([$distante]);
    }

    public function test_documento_attachment_is_stored_in_the_documentos_collection(): void
    {
        Storage::fake('public');

        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        Livewire::test(CreateColaborador::class)
            ->fillForm([
                'setor_id' => $this->setorBetim->id,
                'nome' => 'Fernanda',
                'funcao' => 'Auxiliar',
                'data_admissao' => '2024-03-01',
                'status' => StatusColaborador::Ativo->value,
                'documentos' => [UploadedFile::fake()->createWithContent('rg.pdf', "%PDF-1.4\n%%EOF")],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $colaborador = Colaborador::query()->where('nome', 'Fernanda')->firstOrFail();

        $this->assertCount(1, $colaborador->getMedia(Colaborador::COLECAO_DOCUMENTOS));
    }

    public function test_edit_page_shows_linked_saidas_and_activity_log_history(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);
        $this->actingAs($supervisor);

        $colaborador = $this->criarColaborador(null);

        $colaborador->update(['funcao' => 'Auxiliar Sênior']);

        $categoria = Categoria::create(['nome' => 'EPI']);
        $produto = Produto::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Luva de Segurança',
            'codigo_interno' => 'LUV-001',
            'unidade' => 'PAR',
            'status' => 'ativo',
        ]);
        $motivo = MotivoSaida::create(['nome' => 'Uso operacional']);

        Saida::create([
            'cd_id' => $this->betim->id,
            'produto_id' => $produto->id,
            'produto_variacao_id' => null,
            'quantidade' => 3,
            'colaborador_id' => $colaborador->id,
            'liberado_por' => $supervisor->id,
            'motivo_saida_id' => $motivo->id,
            'status_colaborador_snapshot' => StatusColaborador::Ativo,
            'data' => '2026-07-10',
            'hora' => '10:00',
            'registrado_por' => $supervisor->id,
            'status' => StatusSaida::Ativa,
        ]);

        $this->get(ColaboradorResource::getUrl('edit', ['record' => $colaborador]))->assertOk();

        Livewire::test(SaidasRelationManager::class, [
            'ownerRecord' => $colaborador,
            'pageClass' => EditColaborador::class,
        ])->assertSee('Luva de Segurança');

        Livewire::test(AtividadesRelationManager::class, [
            'ownerRecord' => $colaborador,
            'pageClass' => EditColaborador::class,
        ])->assertSee('Atualizado');
    }
}
