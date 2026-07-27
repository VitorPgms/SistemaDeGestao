<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
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
            // Sem ícone no grupo: cada Resource já define o próprio ícone,
            // e o Filament não permite ícone no grupo e nos itens ao mesmo tempo.
            ->navigationGroups([
                NavigationGroup::make('Cadastros'),
                NavigationGroup::make('Operações'),
                NavigationGroup::make('BI'),
                NavigationGroup::make('Configurações'),
            ])
            // Cada módulo registra seus próprios Filament Resources/Pages/Widgets
            // na sua pasta (app/Modules/{Modulo}/Filament/...), sem precisar
            // tocar neste provider quando um novo módulo do ERP for adicionado.
            ->discoverResources(in: app_path('Modules/Organizacional/Filament/Resources'), for: 'App\Modules\Organizacional\Filament\Resources')
            ->discoverResources(in: app_path('Modules/Estoque/Filament/Resources'), for: 'App\Modules\Estoque\Filament\Resources')
            ->discoverResources(in: app_path('Modules/Usuarios/Filament/Resources'), for: 'App\Modules\Usuarios\Filament\Resources')
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
            // Entradas/Saídas/Estoque são telas Blade (fora do painel Filament),
            // mas entram na mesma navegação lateral para manter uma experiência
            // única — ver App\Modules\Estoque\Http\Controllers.
            ->navigationItems([
                NavigationItem::make('Entradas')
                    ->url(fn (): string => route('entradas.index'))
                    ->icon(Heroicon::OutlinedArrowDownOnSquare)
                    ->group('Operações')
                    ->sort(1)
                    ->visible(fn (): bool => auth()->user()?->can('entradas.view') ?? false),
                NavigationItem::make('Saídas')
                    ->url(fn (): string => route('saidas.index'))
                    ->icon(Heroicon::OutlinedArrowUpOnSquare)
                    ->group('Operações')
                    ->sort(2)
                    ->visible(fn (): bool => auth()->user()?->can('saidas.view') ?? false),
                NavigationItem::make('Estoque')
                    ->url(fn (): string => route('estoque.index'))
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->group('Operações')
                    ->sort(3)
                    ->visible(fn (): bool => auth()->user()?->can('estoque.view') ?? false),
                NavigationItem::make('Inventário')
                    ->url(fn (): string => route('inventarios.index'))
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->group('Operações')
                    ->sort(4)
                    ->visible(fn (): bool => auth()->user()?->can('inventarios.view') ?? false),
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
