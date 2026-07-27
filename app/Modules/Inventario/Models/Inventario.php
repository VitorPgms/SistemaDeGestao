<?php

namespace App\Modules\Inventario\Models;

use App\Modules\Core\Concerns\BelongsToCd;
use App\Modules\Inventario\Enums\StatusInventario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Inventario extends Model
{
    use BelongsToCd;
    use HasFactory;
    use LogsActivity;

    protected $table = 'inventarios';

    protected $fillable = [
        'cd_id',
        'responsavel_id',
        'data_contagem',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data_contagem' => 'date',
        'status' => StatusInventario::class,
    ];

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'responsavel_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(InventarioItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('inventario');
    }
}
