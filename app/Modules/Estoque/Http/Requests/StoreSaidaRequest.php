<?php

namespace App\Modules\Estoque\Http\Requests;

use App\Models\User;
use App\Modules\Estoque\Models\ProdutoVariacao;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Organizacional\Models\Colaborador;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Saida::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cd_id' => [
                Rule::requiredIf(fn () => $this->user()->can('acessar-todos-cds')),
                'nullable',
                'exists:centros_distribuicao,id',
            ],
            'produto_id' => ['required', 'exists:produtos,id'],
            'produto_variacao_id' => ['nullable', 'exists:produto_variacoes,id'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'colaborador_id' => ['required', 'exists:colaboradores,id'],
            'liberado_por' => ['required', 'exists:users,id'],
            'motivo_saida_id' => ['required', 'exists:motivos_saida,id'],
            'data' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function resolvedCdId(): int
    {
        return (int) ($this->input('cd_id') ?: $this->user()->cd_id);
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $cdId = $this->resolvedCdId();

            if (
                $this->filled('colaborador_id') &&
                ! Colaborador::withoutGlobalScopes()->where('id', $this->input('colaborador_id'))->where('cd_id', $cdId)->exists()
            ) {
                $validator->errors()->add('colaborador_id', 'Este colaborador não pertence ao Centro de Distribuição selecionado.');
            }

            if (
                $this->filled('liberado_por') &&
                ! User::where('id', $this->input('liberado_por'))->where('cd_id', $cdId)->exists()
            ) {
                $validator->errors()->add('liberado_por', 'Este usuário não pertence ao Centro de Distribuição selecionado.');
            }

            if (
                $this->filled('produto_variacao_id') &&
                ! ProdutoVariacao::query()->where('id', $this->input('produto_variacao_id'))->where('produto_id', $this->input('produto_id'))->exists()
            ) {
                $validator->errors()->add('produto_variacao_id', 'Esta variação não pertence ao produto selecionado.');
            }
        });
    }
}
