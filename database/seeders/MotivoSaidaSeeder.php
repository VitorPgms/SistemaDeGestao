<?php

namespace Database\Seeders;

use App\Modules\Estoque\Models\MotivoSaida;
use Illuminate\Database\Seeder;

class MotivoSaidaSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            'Uso operacional',
            'Perda',
            'Dano/Quebra',
            'Devolução ao fornecedor',
            'Troca',
            'Ajuste de Inventário',
        ];

        foreach ($motivos as $nome) {
            MotivoSaida::query()->firstOrCreate(['nome' => $nome]);
        }
    }
}
