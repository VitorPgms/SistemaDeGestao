<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Sistema de Gestão')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups([
                NavigationGroup::make('Cadastros')
                    ->icon('heroicon-o-archive-box'),
                NavigationGroup::make('Operações')
                    ->icon('heroicon-o-arrows-right-left'),
                NavigationGroup::make('BI')
                    ->icon('heroicon-o-chart-bar'),
                NavigationGroup::make('Configurações')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            // Cada módulo registra seus próprios Filament Resources/Pages/Widgets
            // na sua pasta (app/Modules/{Modulo}/Filament/...), sem precisar
            // tocar neste provider quando um novo módulo do ERP for adicionado.
            ->discoverResources(in: app_path('Modules/Organizacional/Filament/Resources'), for: 'App\Modules\Organizacional\Filament\Resources')
            ->discoverResources(in: app_path('Modules/Estoque/Filament/Resources'), for: 'App\Modules\Estoque\Filament\Resources')
            ->discoverResources(in: app_path('Modules/Usuarios/Filament/Resources'), for: 'App\Modules\Usuarios\Filament\Resources')
            ->discoverResources(in: app_path('Modules/Inventario/Filament/Resources'), for: 'App\Modules\Inventario\Filament\Resources')
            ->discoverResources(in: app_path('Modules/Configuracoes/Filament/Resources'), for: 'App\Modules\Configuracoes\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
