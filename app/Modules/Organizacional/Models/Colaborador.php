<?php

namespace App\Modules\Organizacional\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Organizacional\Enums\StatusColaborador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Colaborador extends Model
{
    use BelongsToCd;
    use HasFactory;
    use LogsActivity;

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
