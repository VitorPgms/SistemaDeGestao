<?php

namespace App\Modules\Estoque\Models;

use App\Modules\Estoque\Enums\StatusProduto;
use App\Modules\Estoque\Enums\TipoVariacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Produto extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;

    public const COLECAO_MANUAL = 'manual';

    public const COLECAO_FICHA_TECNICA = 'ficha_tecnica';

    protected $table = 'produtos';

    protected $fillable = [
        'categoria_id',
        'nome',
        'codigo_interno',
        'codigo_barras',
        'unidade',
        'descricao',
        'tipo_variacao',
        'status',
    ];

    protected $casts = [
        'tipo_variacao' => TipoVariacao::class,
        'status' => StatusProduto::class,
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function variacoes(): HasMany
    {
        return $this->hasMany(ProdutoVariacao::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLECAO_MANUAL)
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection(self::COLECAO_FICHA_TECNICA)
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->useLogName('estoque');
    }
}
