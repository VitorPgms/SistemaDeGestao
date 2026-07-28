<?php

namespace App\Modules\Estoque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estoque\Filament\Pages\EstoqueLista;
use App\Modules\Estoque\Models\Estoque;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    public function update(Request $request, Estoque $estoque): RedirectResponse
    {
        $this->authorize('update', $estoque);

        $dados = $request->validate([
            'quantidade_minima' => ['required', 'integer', 'min:0'],
            'quantidade_ideal' => ['required', 'integer', 'min:0', 'gte:quantidade_minima'],
            'localizacao' => ['nullable', 'string', 'max:255'],
        ]);

        $estoque->update($dados);

        return redirect(EstoqueLista::getUrl())->with('sucesso', 'Parâmetros de estoque atualizados com sucesso.');
    }
}
