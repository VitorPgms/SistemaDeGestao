<?php

namespace Tests\Feature\Estoque;

use App\Models\User;
use App\Modules\Estoque\Enums\StatusProduto;
use App\Modules\Estoque\Enums\TipoVariacao;
use App\Modules\Estoque\Filament\Resources\Produtos\Pages\CreateProduto;
use App\Modules\Estoque\Filament\Resources\Produtos\Pages\ListProdutos;
use App\Modules\Estoque\Models\Categoria;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProdutoResourceTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    private CentroDistribuicao $goiania;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'produtos.view', 'produtos.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('supervisor', 'web')->givePermissionTo(['produtos.view']);

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);
        $this->goiania = CentroDistribuicao::create(['nome' => 'Goiânia', 'codigo' => 'GO01']);
        $this->categoria = Categoria::create(['nome' => 'EPI']);
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

    public function test_produto_created_in_one_cd_is_visible_to_users_from_other_cds(): void
    {
        $admin = $this->userComPapel('administrador', $this->betim);
        $this->actingAs($admin);

        Produto::create([
            'categoria_id' => $this->categoria->id,
            'nome' => 'Botina Bracol CA 12345',
            'codigo_interno' => 'BOT-001',
            'unidade' => 'PAR',
            'status' => StatusProduto::Ativo,
        ]);

        $supervisorGoiania = $this->userComPapel('supervisor', $this->goiania);
        $this->actingAs($supervisorGoiania);

        Livewire::test(ListProdutos::class)
            ->assertCanSeeTableRecords(Produto::all());
    }

    public function test_supervisor_cannot_create_produtos(): void
    {
        $supervisor = $this->userComPapel('supervisor', $this->betim);

        $this->assertFalse($supervisor->can('create', Produto::class));
    }

    public function test_creating_produto_with_variacoes_persists_each_variacao(): void
    {
        $admin = $this->userComPapel('administrador', $this->betim);
        $this->actingAs($admin);

        Livewire::test(CreateProduto::class)
            ->fillForm([
                'categoria_id' => $this->categoria->id,
                'nome' => 'Botina Bracol CA 12345',
                'codigo_interno' => 'BOT-002',
                'unidade' => 'PAR',
                'tipo_variacao' => TipoVariacao::Numeracao->value,
                'status' => StatusProduto::Ativo->value,
                'variacoes' => [
                    ['valor' => '40', 'ativo' => true],
                    ['valor' => '41', 'ativo' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $produto = Produto::query()->where('codigo_interno', 'BOT-002')->firstOrFail();

        $this->assertSame(['40', '41'], $produto->variacoes()->orderBy('ordem')->pluck('valor')->all());
    }

    public function test_manual_pdf_attachment_is_stored_in_the_manual_collection(): void
    {
        Storage::fake('public');

        $admin = $this->userComPapel('administrador', $this->betim);
        $this->actingAs($admin);

        Livewire::test(CreateProduto::class)
            ->fillForm([
                'categoria_id' => $this->categoria->id,
                'nome' => 'Capacete de Segurança',
                'codigo_interno' => 'CAP-001',
                'unidade' => 'UN',
                'status' => StatusProduto::Ativo->value,
                'manual' => [UploadedFile::fake()->createWithContent('manual.pdf', "%PDF-1.4\n%%EOF")],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $produto = Produto::query()->where('codigo_interno', 'CAP-001')->firstOrFail();

        $this->assertCount(1, $produto->getMedia(Produto::COLECAO_MANUAL));
    }
}
