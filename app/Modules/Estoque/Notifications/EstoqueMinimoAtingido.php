<?php

namespace App\Modules\Estoque\Notifications;

use App\Modules\Estoque\Models\Estoque;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EstoqueMinimoAtingido extends Notification
{
    use Queueable;

    public function __construct(private readonly Estoque $estoque)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $descricao = $this->estoque->produto->nome
            . ($this->estoque->produtoVariacao ? " ({$this->estoque->produtoVariacao->valor})" : '');

        return [
            'estoque_id' => $this->estoque->id,
            'produto_id' => $this->estoque->produto_id,
            'cd_id' => $this->estoque->cd_id,
            'titulo' => 'Estoque mínimo atingido',
            'mensagem' => "{$descricao} atingiu o estoque mínimo em {$this->estoque->centroDistribuicao->nome} (atual: {$this->estoque->quantidade_atual}, mínimo: {$this->estoque->quantidade_minima}).",
        ];
    }
}
