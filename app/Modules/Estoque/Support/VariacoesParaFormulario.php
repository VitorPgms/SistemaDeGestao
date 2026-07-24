<?php

namespace App\Modules\Estoque\Support;

use App\Modules\Estoque\Enums\TipoVariacao;
use Illuminate\Support\Collection;

/**
 * Serializa produtos (com variações já carregadas) para um array simples,
 * pronto para @json() nas views de Entrada/Saída — o JS puro na página usa
 * isso para mostrar/popular o select de variação conforme o produto escolhido.
 */
class VariacoesParaFormulario
{
    /**
     * @return array<int, array{temVariacao: bool, variacoes: array<int, array{id: int, valor: string}>}>
     */
    public static function mapear(Collection $produtos): array
    {
        return $produtos->mapWithKeys(function ($produto): array {
            $variacoes = $produto->variacoes
                ->map(fn ($variacao): array => ['id' => $variacao->id, 'valor' => $variacao->valor])
                ->values()
                ->all();

            return [
                $produto->id => [
                    'temVariacao' => $produto->tipo_variacao !== TipoVariacao::Nenhum,
                    'variacoes' => $variacoes,
                ],
            ];
        })->all();
    }
}
