<?php

namespace Database\Seeders;

use App\Modules\Organizacional\Models\CentroDistribuicao;
use Illuminate\Database\Seeder;

class CentroDistribuicaoSeeder extends Seeder
{
    public function run(): void
    {
        $centros = [
            ['nome' => 'Betim', 'codigo' => 'BH01', 'cidade' => 'Betim', 'estado' => 'MG'],
            ['nome' => 'Goiânia', 'codigo' => 'GO01', 'cidade' => 'Goiânia', 'estado' => 'GO'],
            ['nome' => 'Feira de Santana', 'codigo' => 'BA01', 'cidade' => 'Feira de Santana', 'estado' => 'BA'],
            ['nome' => 'Brasília', 'codigo' => 'DF01', 'cidade' => 'Brasília', 'estado' => 'DF'],
        ];

        foreach ($centros as $centro) {
            CentroDistribuicao::query()->updateOrCreate(
                ['codigo' => $centro['codigo']],
                $centro,
            );
        }
    }
}
