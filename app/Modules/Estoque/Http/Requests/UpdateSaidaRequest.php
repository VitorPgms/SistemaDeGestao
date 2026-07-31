<?php

namespace App\Modules\Estoque\Http\Requests;

use App\Models\User;
use App\Modules\Estoque\Models\ProdutoVariacao;
use App\Modules\Organizacional\Models\Colaborador;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('saida'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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

    /**
     * O CD de uma saída nunca é editável — permanece o mesmo definido no
     * registro original, para não permitir "transferir" o movimento entre CDs.
     */
    public function resolvedCdId(): int
    {
        return $this->route('saida')->cd_id;
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $cdId = $this->resolvedCdId();

            if (
                $this->filled('colaborador_id') &&
                ! Colaborador::withoutGlobalScopes()->where('id', $this->input('colaborador_id'))->where('cd_id', $cdId)->exists()
            ) {
                $validator->errors()->add('colaborador_id', 'Este colaborador não pertence ao Centro de Distribuição da saída.');
            }

            if (
                $this->filled('liberado_por') &&
                ! User::where('id', $this->input('liberado_por'))->where('cd_id', $cdId)->exists()
            ) {
                $validator->errors()->add('liberado_por', 'Este usuário não pertence ao Centro de Distribuição da saída.');
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
