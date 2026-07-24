<?php

namespace App\Modules\Estoque\Http\Requests;

use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\ProdutoVariacao;
use App\Modules\Organizacional\Models\Colaborador;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Entrada::class);
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
            'fornecedor_id' => ['required', 'exists:fornecedores,id'],
            'numero_nota_fiscal' => ['required', 'string', 'max:255'],
            'data_compra' => ['required', 'date'],
            'data_entrega' => ['required', 'date', 'after_or_equal:data_compra'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'colaborador_recebimento_id' => ['required', 'exists:colaboradores,id'],
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
                $this->filled('fornecedor_id') &&
                ! Fornecedor::withoutGlobalScopes()->where('id', $this->input('fornecedor_id'))->where('cd_id', $cdId)->exists()
            ) {
                $validator->errors()->add('fornecedor_id', 'Este fornecedor não pertence ao Centro de Distribuição selecionado.');
            }

            if (
                $this->filled('colaborador_recebimento_id') &&
                ! Colaborador::withoutGlobalScopes()->where('id', $this->input('colaborador_recebimento_id'))->where('cd_id', $cdId)->exists()
            ) {
                $validator->errors()->add('colaborador_recebimento_id', 'Este colaborador não pertence ao Centro de Distribuição selecionado.');
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
