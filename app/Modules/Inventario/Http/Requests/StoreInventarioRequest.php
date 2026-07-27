<?php

namespace App\Modules\Inventario\Http\Requests;

use App\Modules\Inventario\Models\Inventario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Inventario::class);
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
            'data_contagem' => ['required', 'date'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function resolvedCdId(): int
    {
        return (int) ($this->input('cd_id') ?: $this->user()->cd_id);
    }
}
