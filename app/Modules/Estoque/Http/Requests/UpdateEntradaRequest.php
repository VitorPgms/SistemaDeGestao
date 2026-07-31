<?php

namespace App\Modules\Estoque\Http\Requests;

use App\Modules\Estoque\Models\Entrada;
use App\Modules\Estoque\Models\Fornecedor;
use App\Modules\Estoque\Models\ProdutoVariacao;
use App\Modules\Estoque\Models\ResponsavelRecebimento;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEntradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('entrada'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'produto_id' => ['required', 'exists:produtos,id'],
            'produto_variacao_id' => ['nullable', 'exists:produto_variacoes,id'],
            'fornecedor_id' => ['required', 'exists:fornecedores,id'],
            'numero_nota_fiscal' => ['required', 'string', 'max:255'],
            'data_compra' => ['required', 'date'],
            'data_entrega' => ['required', 'date', 'after_or_equal:data_compra'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'responsavel_recebimento_id' => ['required', 'exists:responsaveis_recebimento,id'],
            'observacoes' => ['nullable', 'string'],
            'nota_fiscal_anexo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * O CD de uma entrada nunca é editável — permanece o mesmo definido no
     * registro original, para não permitir "transferir" o movimento entre CDs.
     */
    public function resolvedCdId(): int
    {
        return $this->route('entrada')->cd_id;
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $cdId = $this->resolvedCdId();

            if (
                $this->filled('fornecedor_id') &&
                ! Fornecedor::withoutGlobalScopes()->where('id', $this->input('fornecedor_id'))->where('cd_id', $cdId)->exists()
            ) {
                $validator->errors()->add('fornecedor_id', 'Este fornecedor não pertence ao Centro de Distribuição da entrada.');
            }

            if (
                $this->filled('responsavel_recebimento_id') &&
                ! ResponsavelRecebimento::withoutGlobalScopes()->where('id', $this->input('responsavel_recebimento_id'))->where('cd_id', $cdId)->exists()
            ) {
                $validator->errors()->add('responsavel_recebimento_id', 'Este responsável não pertence ao Centro de Distribuição da entrada.');
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
