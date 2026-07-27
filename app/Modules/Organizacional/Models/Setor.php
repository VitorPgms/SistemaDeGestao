<?php

namespace App\Modules\Organizacional\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Organizacional\Enums\StatusColaborador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Setor extends Model
{
    use BelongsToCd;
    use HasFactory;
    use LogsActivity;

    protected $table = 'setores';

    protected $fillable = [
        'cd_id',
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Impede desativar um setor que ainda possua colaboradores ativos vinculados.
     */
    protected static function booted(): void
    {
        static::saving(function (Setor $setor) {
            if ($setor->exists && $setor->isDirty('ativo') && ! $setor->ativo) {
                $possuiColaboradorAtivo = $setor->colaboradores()
                    ->where('status', StatusColaborador::Ativo)
                    ->exists();

                if ($possuiColaboradorAtivo) {
                    throw ValidationException::withMessages([
                        'ativo' => 'Não é possível desativar um setor com colaboradores ativos.',
                    ]);
                }
            }
        });
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('organizacional');
    }
}
