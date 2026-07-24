<?php

namespace Tests\Feature\Organizacional;

use App\Models\User;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use App\Modules\Usuarios\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Usuarios\Filament\Resources\Users\Pages\ListUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private CentroDistribuicao $betim;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['acessar-todos-cds', 'usuarios.view', 'usuarios.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('administrador', 'web')->givePermissionTo('acessar-todos-cds');
        Role::findOrCreate('supervisor', 'web');

        $this->betim = CentroDistribuicao::create(['nome' => 'Betim', 'codigo' => 'BH01']);
    }

    private function admin(): User
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $admin->assignRole('administrador');

        return $admin;
    }

    public function test_administrador_can_create_user_with_a_single_role(): void
    {
        $this->actingAs($this->admin());

        $supervisorRoleId = Role::findOrCreate('supervisor', 'web')->id;

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Novo Supervisor',
                'email' => 'supervisor@teste.local',
                'cd_id' => $this->betim->id,
                'roles' => [$supervisorRoleId],
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'supervisor@teste.local')->firstOrFail();

        $this->assertTrue($user->hasRole('supervisor'));
        $this->assertSame($this->betim->id, $user->cd_id);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $user->password));
    }

    public function test_supervisor_without_usuarios_permission_cannot_access_user_list(): void
    {
        $supervisor = User::create([
            'name' => 'Supervisor',
            'email' => 'sup@teste.local',
            'password' => bcrypt('password'),
            'cd_id' => $this->betim->id,
            'ativo' => true,
        ]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);

        Livewire::test(ListUsers::class)
            ->assertForbidden();
    }
}
