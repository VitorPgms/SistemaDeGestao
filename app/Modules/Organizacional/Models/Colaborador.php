<?php

namespace App\Modules\Organizacional\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Estoque\Models\Saida;
use App\Modules\Organizacional\Enums\StatusColaborador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Colaborador extends Model implements HasMedia
{
    use BelongsToCd;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;

    public const COLECAO_DOCUMENTOS = 'documentos';

    /**
     * Defesa em profundidade: garante consistência status/data_demissão e que o
     * setor pertence ao mesmo CD do colaborador, mesmo fora do formulário Filament.
     */
    protected static function booted(): void
    {
        static::saving(function (Colaborador $colaborador) {
            $inativo = $colaborador->status === StatusColaborador::Inativo;

            if ($inativo && ! $colaborador->data_demissao) {
                throw ValidationException::withMessages([
                    'data_demissao' => 'Colaborador inativo deve possuir data de demissão.',
                ]);
            }

            if (! $inativo && $colaborador->data_demissao) {
                throw ValidationException::withMessages([
                    'data_demissao' => 'Colaborador ativo não pode possuir data de demissão.',
                ]);
            }

            if ($colaborador->setor_id) {
                $setorCdId = Setor::query()->whereKey($colaborador->setor_id)->value('cd_id');

                if ($setorCdId !== null && $setorCdId !== $colaborador->cd_id) {
                    throw ValidationException::withMessages([
                        'setor_id' => 'O setor selecionado pertence a outro centro de distribuição.',
                    ]);
                }
            }
        });
    }

    public const DIAS_ALERTA_EXAME_PERIODICO = 30;

    protected $table = 'colaboradores';

    protected $fillable = [
        'cd_id',
        'setor_id',
        'nome',
        'data_nascimento',
        'funcao',
        'tamanho_vestimenta',
        'data_admissao',
        'data_demissao',
        'data_ultimo_exame_periodico',
        'data_proximo_exame_periodico',
        'status',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_admissao' => 'date',
        'data_demissao' => 'date',
        'data_ultimo_exame_periodico' => 'date',
        'data_proximo_exame_periodico' => 'date',
        'status' => StatusColaborador::class,
    ];

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function saidas(): HasMany
    {
        return $this->hasMany(Saida::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLECAO_DOCUMENTOS)
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    /**
     * 'vencido' (data já passou), 'proximo' (dentro do período de alerta) ou
     * 'normal'. Retorna null quando não há data de próximo exame cadastrada.
     */
    public function statusExamePeriodico(): ?string
    {
        if (! $this->data_proximo_exame_periodico) {
            return null;
        }

        $hoje = now()->startOfDay();
        $vencimento = $this->data_proximo_exame_periodico->copy()->startOfDay();

        if ($vencimento->lessThan($hoje)) {
            return 'vencido';
        }

        if ($hoje->diffInDays($vencimento) <= self::DIAS_ALERTA_EXAME_PERIODICO) {
            return 'proximo';
        }

        return 'normal';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('organizacional');
    }
}
