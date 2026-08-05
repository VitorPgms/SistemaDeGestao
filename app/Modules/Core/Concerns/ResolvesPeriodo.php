<?php

namespace App\Modules\Core\Concerns;

use Illuminate\Support\Carbon;

/**
 * Opções práticas de período (não é um seletor de datas livre por padrão):
 * hoje, últimos 7/30 dias, este mês, mês anterior, ou personalizado (usa
 * data_inicio/data_fim da query string). Extraído do Dashboard para ser
 * reaproveitado por qualquer outra tela que precise do mesmo filtro.
 */
trait ResolvesPeriodo
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolverPeriodo(): array
    {
        $periodo = request()->input('periodo', 'este_mes');

        return match ($periodo) {
            'hoje' => [now()->startOfDay(), now()->endOfDay()],
            '7dias' => [now()->copy()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30dias' => [now()->copy()->subDays(29)->startOfDay(), now()->endOfDay()],
            'mes_anterior' => [now()->copy()->subMonthNoOverflow()->startOfMonth(), now()->copy()->subMonthNoOverflow()->endOfMonth()],
            'personalizado' => [
                request()->filled('data_inicio') ? Carbon::parse(request()->input('data_inicio'))->startOfDay() : now()->startOfMonth(),
                request()->filled('data_fim') ? Carbon::parse(request()->input('data_fim'))->endOfDay() : now()->endOfMonth(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
