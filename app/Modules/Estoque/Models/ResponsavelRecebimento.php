<?php

namespace App\Modules\Estoque\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ResponsavelRecebimento extends Model
{
    use BelongsToCd;
    use HasFactory;
    use LogsActivity;

    protected $table = 'responsaveis_recebimento';

    protected $fillable = [
        'cd_id',
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('estoque');
    }
}
