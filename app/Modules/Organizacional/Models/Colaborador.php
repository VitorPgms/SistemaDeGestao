<?php

namespace App\Modules\Organizacional\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Organizacional\Enums\StatusColaborador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Colaborador extends Model
{
    use BelongsToCd;
    use HasFactory;
    use LogsActivity;

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

    protected $table = 'colaboradores';

    protected $fillable = [
        'cd_id',
        'setor_id',
        'nome',
        'funcao',
        'data_admissao',
        'data_demissao',
        'status',
    ];

    protected $casts = [
        'data_admissao' => 'date',
        'data_demissao' => 'date',
        'status' => StatusColaborador::class,
    ];

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('organizacional');
    }
}
