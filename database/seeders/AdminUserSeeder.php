<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $cdBetim = CentroDistribuicao::query()->where('codigo', 'BH01')->firstOrFail();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@sistemagestao.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'cd_id' => $cdBetim->id,
                'ativo' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['administrador']);
    }
}
