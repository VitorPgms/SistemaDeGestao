<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Organizacional\Models\CentroDistribuicao;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'cd_id', 'ativo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    /**
     * Sem CdScope aqui de propósito: o model User é resolvido pelo próprio
     * Auth::user() durante a autenticação da sessão, e um Global Scope que
     * dependesse de Auth::user() para se aplicar causaria recursão infinita
     * (resolver o usuário dispara a query, a query dispara o scope, o scope
     * chama Auth::user() de novo antes da primeira resolução terminar).
     * O filtro por CD da listagem de usuários é aplicado explicitamente em
     * UserResource::getEloquentQuery().
     */
    protected static function booted(): void
    {
        static::saving(function (Model $model) {
            $user = Auth::user();

            if (! $user || $user->can('acessar-todos-cds')) {
                return;
            }

            $model->cd_id = $user->cd_id;
        });
    }

    public function centroDistribuicao(): BelongsTo
    {
        return $this->belongsTo(CentroDistribuicao::class, 'cd_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->ativo;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logExcept(['password', 'remember_token'])
            ->useLogName('usuarios');
    }
}
