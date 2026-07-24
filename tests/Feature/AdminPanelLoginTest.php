<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sistema de Gestão');
    }

    public function test_active_user_can_log_into_the_panel(): void
    {
        Role::findOrCreate('administrador', 'web');

        $cd = CentroDistribuicao::create([
            'nome' => 'Betim',
            'codigo' => 'BH01',
            'ativo' => true,
        ]);

        $user = User::create([
            'name' => 'Administrador',
            'email' => 'admin@sistemagestao.local',
            'password' => bcrypt('password'),
            'cd_id' => $cd->id,
            'ativo' => true,
        ]);
        $user->assignRole('administrador');

        Livewire::test(Login::class)
            ->set('data.email', 'admin@sistemagestao.local')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_dashboard_renders_for_an_authenticated_admin(): void
    {
        Role::findOrCreate('administrador', 'web')->givePermissionTo(
            \Spatie\Permission\Models\Permission::findOrCreate('acessar-todos-cds', 'web'),
        );

        $cd = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);

        $user = User::create([
            'name' => 'Administrador',
            'email' => 'admin@sistemagestao.local',
            'password' => bcrypt('password'),
            'cd_id' => $cd->id,
            'ativo' => true,
        ]);
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_dashboard_renders_after_a_real_session_login_not_actingAs(): void
    {
        // actingAs() injeta o usuário direto no Guard, sem passar pela sessão.
        // Este teste simula o fluxo real de navegador: login grava o ID na
        // sessão, e a requisição seguinte precisa re-resolver o usuário via
        // SessionGuard::user() -> provider->retrieveById() a partir do zero.
        Role::findOrCreate('administrador', 'web')->givePermissionTo(
            \Spatie\Permission\Models\Permission::findOrCreate('acessar-todos-cds', 'web'),
        );

        $cd = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);

        $user = User::create([
            'name' => 'Administrador',
            'email' => 'admin@sistemagestao.local',
            'password' => bcrypt('password'),
            'cd_id' => $cd->id,
            'ativo' => true,
        ]);
        $user->assignRole('administrador');

        Livewire::test(Login::class)
            ->set('data.email', 'admin@sistemagestao.local')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoFormErrors();

        // Nova requisição HTTP "de verdade", que precisa reautenticar via sessão.
        $this->get('/admin')->assertOk();
    }

    public function test_inactive_user_cannot_access_the_panel(): void
    {
        $cd = CentroDistribuicao::create([
            'nome' => 'Betim',
            'codigo' => 'BH01',
            'ativo' => true,
        ]);

        $user = User::create([
            'name' => 'Ex-funcionário',
            'email' => 'inativo@sistemagestao.local',
            'password' => bcrypt('password'),
            'cd_id' => $cd->id,
            'ativo' => false,
        ]);

        Livewire::test(Login::class)
            ->set('data.email', 'inativo@sistemagestao.local')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }
}
