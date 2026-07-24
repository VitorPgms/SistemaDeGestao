<?php

namespace App\Modules\Organizacional\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
