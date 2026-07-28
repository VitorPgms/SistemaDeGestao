<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Modules\Inventario\Filament\Pages\InventarioDetalhe;
use App\Modules\Inventario\Filament\Pages\Inventarios;
use App\Modules\Inventario\Filament\Pages\NovoInventario;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
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
            // Navegação única entre Cadastros, Operações e BI: todos são
            // Filament Resources/Pages, então compartilham o mesmo shell e
            // a mesma transição SPA nativamente — ver seção Navegação do
            // CONTEXT.md/DECISIONS.md.
            ->spa()
            // CSS/JS do app (Tailwind dos componentes Blade próprios e
            // Chart.js do Dashboard) são carregados via Vite; dentro do
            // painel Filament, o layout nativo não conhece esse bundle,
            // então é injetado aqui para ficar disponível em qualquer
            // Page que reaproveite views/componentes Blade do app.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render("@vite(['resources/css/app.css', 'resources/js/app.js'])"),
            )
            // Mensagens de sucesso/erro (session('sucesso')/session('erro'))
            // usadas pelos redirects dos Controllers de Operações — um único
            // lugar em vez de repetir em cada view.
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => view('filament.flash-messages')->render(),
            )
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
            ->discoverPages(in: app_path('Modules/Bi/Filament/Pages'), for: 'App\Modules\Bi\Filament\Pages')
            ->discoverPages(in: app_path('Modules/Estoque/Filament/Pages'), for: 'App\Modules\Estoque\Filament\Pages')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            // Inventário é registrado manualmente (não via discoverPages) porque
            // a ordem importa: "inventarios/novo" e "inventarios/{inventario}"
            // têm o mesmo número de segmentos, e a rota curinga precisa vir
            // depois da rota estática, senão "novo" é lido como um ID.
            ->pages([
                Dashboard::class,
                Inventarios::class,
                NovoInventario::class,
                InventarioDetalhe::class,
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
