<?php

namespace App\Modules\Organizacional\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CentroDistribuicao extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'centros_distribuicao';

    protected $fillable = [
        'nome',
        'codigo',
        'endereco',
        'cidade',
        'estado',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'cd_id');
    }

    public function setores(): HasMany
    {
        return $this->hasMany(Setor::class, 'cd_id');
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'cd_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('organizacional');
    }
}
