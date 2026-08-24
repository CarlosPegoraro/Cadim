<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cadim</title>
    <script>
        window.applySavedTheme = () => {
            document.documentElement.dataset.theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light';
        };

        window.applySavedTheme();

        window.syncThemeToggle = () => {
            const dark = document.documentElement.dataset.theme === 'dark';
            document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                button.classList.toggle('is-dark', dark);
                button.setAttribute('aria-pressed', dark ? 'true' : 'false');
                button.setAttribute('aria-label', dark ? 'Ativar modo claro' : 'Ativar modo escuro');
                button.querySelector('[data-theme-icon]')?.replaceChildren(document.createTextNode(dark ? '☀' : '☾'));
            });
        };

        window.toggleTheme = (event) => {
            event?.stopPropagation();
            const dark = document.documentElement.dataset.theme === 'dark';
            document.documentElement.dataset.theme = dark ? 'light' : 'dark';
            localStorage.setItem('theme', dark ? 'light' : 'dark');
            window.syncThemeToggle();
        };

        document.addEventListener('DOMContentLoaded', window.syncThemeToggle);
        document.addEventListener('livewire:navigating', window.applySavedTheme);
        document.addEventListener('livewire:navigated', () => {
            window.applySavedTheme();
            window.syncThemeToggle();
        });
        document.addEventListener('alpine:navigated', window.applySavedTheme);
    </script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style id="theme-overrides">
        html[data-theme="dark"] { color-scheme: dark; }
        html[data-theme="dark"] body.app-shell { background: #0f1713 !important; color: #dbe7df !important; }
        html[data-theme="dark"] .app-sidebar,
        html[data-theme="dark"] .topbar,
        html[data-theme="dark"] .card,
        html[data-theme="dark"] .panel,
        html[data-theme="dark"] .metric-card,
        html[data-theme="dark"] .card-widget,
        html[data-theme="dark"] .side-panel { background: #17221c !important; border-color: #2a3a30 !important; color: #dbe7df !important; }
        html[data-theme="dark"] .bg-white,
        html[data-theme="dark"] .bg-stone-50,
        html[data-theme="dark"] .bg-slate-50 { background-color: #1d2a22 !important; }
        html[data-theme="dark"] .text-slate-900,
        html[data-theme="dark"] .text-slate-800,
        html[data-theme="dark"] .text-slate-700 { color: #e8f2eb !important; }
        html[data-theme="dark"] .text-slate-600 { color: #c9d8ce !important; }
        html[data-theme="dark"] .text-slate-500 { color: #b0c3b6 !important; }
        html[data-theme="dark"] .text-slate-400,
        html[data-theme="dark"] .text-slate-300,
        html[data-theme="dark"] .metric-card small,
        html[data-theme="dark"] .panel-title p,
        html[data-theme="dark"] .dashboard-table td small,
        html[data-theme="dark"] .upcoming-list small,
        html[data-theme="dark"] .limit-subtitle { color: #9caf9f !important; }
        html[data-theme="dark"] .metric-card p,
        html[data-theme="dark"] .field,
        html[data-theme="dark"] .dashboard-table td,
        html[data-theme="dark"] .upcoming-list b { color: #c9d8ce !important; }
        html[data-theme="dark"] .panel-title h2,
        html[data-theme="dark"] .side-heading h2,
        html[data-theme="dark"] .dashboard-heading h1,
        html[data-theme="dark"] .metric-card strong,
        html[data-theme="dark"] .limit-value { color: #f1f7f3 !important; }
        html[data-theme="dark"] .text-emerald-700,
        html[data-theme="dark"] .text-emerald-800,
        html[data-theme="dark"] .text-emerald-600,
        html[data-theme="dark"] .amount-income,
        html[data-theme="dark"] .side-heading a { color: #72e6a0 !important; }
        html[data-theme="dark"] .text-rose-600,
        html[data-theme="dark"] .amount-expense { color: #ff9b9b !important; }
        html[data-theme="dark"] .text-amber-700 { color: #fbd176 !important; }
        html[data-theme="dark"] .field input,
        html[data-theme="dark"] .field select,
        html[data-theme="dark"] .field textarea,
        html[data-theme="dark"] .filter-control { color: #e8f2eb !important; }
        html[data-theme="dark"] .support-hero { background: linear-gradient(135deg, #203c29, #1c3023) !important; }
        html[data-theme="dark"] .support-hero h1,
        html[data-theme="dark"] .support-article h2,
        html[data-theme="dark"] .help-dialog h2,
        html[data-theme="dark"] .help-step-content h3 { color: #f1f7f3 !important; }
        html[data-theme="dark"] .support-hero p:not(.eyebrow),
        html[data-theme="dark"] .support-summary,
        html[data-theme="dark"] .support-article ol,
        html[data-theme="dark"] .help-dialog-intro,
        html[data-theme="dark"] .help-step-content p { color: #b8cabe !important; }
        html[data-theme="dark"] .support-search { background: #17221c !important; border-color: #2a3a30 !important; }
        html[data-theme="dark"] .support-search input { color: #e8f2eb !important; }
        html[data-theme="dark"] .support-article { background: #17221c !important; border-color: #2a3a30 !important; }
    </style>
    <style id="help-popover-final">
        /* Final visual override: this tour is an anchored popover, never a centered modal. */
        .help-modal { position: fixed !important; inset: 0 !important; display: block !important; z-index: 100000 !important; pointer-events: none !important; }
        .help-modal[hidden] { display: none !important; }
        .help-backdrop { display: none !important; }
        .help-dialog.help-popover { position: fixed !important; display: block !important; width: min(290px, calc(100vw - 32px)) !important; min-height: 0 !important; padding: 20px !important; border: 0 !important; border-radius: 14px !important; background: #23804b !important; color: #fff !important; box-shadow: 0 12px 30px rgba(27, 89, 48, .3) !important; pointer-events: auto !important; }
        .help-dialog.help-popover::before { content: '' !important; position: absolute !important; width: 14px !important; height: 14px !important; background: #23804b !important; transform: rotate(45deg) !important; }
        .help-dialog.help-popover[data-placement="bottom"]::before { top: -7px !important; left: calc(50% - 7px) !important; }
        .help-dialog.help-popover[data-placement="top"]::before { bottom: -7px !important; left: calc(50% - 7px) !important; }
        .help-dialog.help-popover[data-placement="right"]::before { left: -7px !important; top: calc(50% - 7px) !important; }
        .help-dialog.help-popover[data-placement="left"]::before { right: -7px !important; top: calc(50% - 7px) !important; }
        .help-dialog.help-popover > .help-dialog-icon,
        .help-dialog.help-popover > .eyebrow,
        .help-dialog.help-popover > h2,
        .help-dialog.help-popover > .help-dialog-intro { display: none !important; }
        .help-dialog.help-popover .help-close { color: rgba(255,255,255,.72) !important; }
        .help-dialog.help-popover .help-step-count { margin-top: 0 !important; color: rgba(255,255,255,.7) !important; }
        .help-dialog.help-popover .help-step-content { min-height: 80px !important; margin-top: 4px !important; }
        .help-dialog.help-popover .help-step-content h3 { color: #fff !important; font-size: 16px !important; }
        .help-dialog.help-popover .help-step-content p { color: rgba(255,255,255,.9) !important; font-size: 14px !important; }
        .help-dialog.help-popover .help-nav-button { background: rgba(255,255,255,.15) !important; color: #fff !important; }
    </style>
    @livewireStyles
</head>
@php
    $helpContent = [
        'dashboard' => ['title' => 'Conheça seu Dashboard', 'intro' => 'Veja um resumo da sua vida financeira e acompanhe o que merece atenção.', 'steps' => [['selector' => '.dashboard-heading', 'title' => 'Seu ponto de partida', 'text' => 'Aqui você encontra o período analisado e pode criar uma nova transação rapidamente.'], ['selector' => '.summary-grid', 'title' => 'Os números que importam', 'text' => 'Receitas, despesas e saldos ajudam você a entender o momento atual e o que está por vir.'], ['selector' => '.chart-panel', 'title' => 'Veja a evolução', 'text' => 'Compare receitas e despesas no período selecionado.'], ['selector' => '.transactions-panel', 'title' => 'Confira os lançamentos', 'text' => 'Use este histórico para validar se as informações estão corretas.']]],
        'transactions' => ['title' => 'Vamos organizar suas transações', 'intro' => 'Registre cada entrada e saída para o Dashboard trabalhar por você.', 'steps' => [['selector' => '.mb-7', 'title' => 'Ações rápidas', 'text' => 'Crie uma transação, importe um CSV ou exporte seus dados.'], ['selector' => '.card.mb-5', 'title' => 'Encontre qualquer lançamento', 'text' => 'Combine busca, tipo, status e datas. Os filtros atualizam a lista na hora.'], ['selector' => '[data-shortcut-new]', 'title' => 'Comece um lançamento', 'text' => 'Descreva o movimento, escolha a origem e defina quando ele acontece.']]],
        'accounts' => ['title' => 'Contas e cartões', 'intro' => 'Mantenha suas fontes de dinheiro organizadas para ter saldos confiáveis.', 'steps' => [['selector' => '.accounts-tabs', 'title' => 'Separe cada visão', 'text' => 'Alterne entre contas, cartões e transferências usando as abas.'], ['selector' => '.tab-panel', 'title' => 'Cadastre seus recursos', 'text' => 'Use os formulários para adicionar contas e cartões com suas informações reais.']]],
        'categories' => ['title' => 'Categorias deixam tudo claro', 'intro' => 'Uma boa categoria ajuda você a entender para onde seu dinheiro está indo.', 'steps' => [['selector' => '.card', 'title' => 'Crie uma categoria', 'text' => 'Escolha um nome fácil de reconhecer e defina se ela é uma entrada ou saída.']]],
        'budgets' => ['title' => 'Planeje seus próximos passos', 'intro' => 'Orçamentos controlam gastos; metas dão direção para o dinheiro que você quer guardar.', 'steps' => [['selector' => '.tabs-list', 'title' => 'Escolha o objetivo', 'text' => 'Alterne entre Orçamentos e Metas.'], ['selector' => '.tab-panel', 'title' => 'Acompanhe o progresso', 'text' => 'Crie um plano e volte aqui para acompanhar quanto já foi usado ou guardado.']]],
        'profile' => ['title' => 'Seu perfil', 'intro' => 'Mantenha seus dados e sua segurança sempre em dia.', 'steps' => [['selector' => '.settings-grid', 'title' => 'Duas áreas importantes', 'text' => 'Atualize seus dados pessoais no primeiro cartão e sua senha no segundo.']]],
        'support' => ['title' => 'Como usar a central', 'intro' => 'Encontre um artigo ou faça o tour para entender esta página.', 'steps' => [['selector' => '.support-toolbar', 'title' => 'Busque uma resposta', 'text' => 'Digite uma palavra para filtrar os artigos da base de conhecimento.'], ['selector' => '.support-grid', 'title' => 'Guias práticos', 'text' => 'Abra qualquer artigo para ver o passo a passo completo.']]],
        'changelog' => ['title' => 'Acompanhe as novidades', 'intro' => 'Veja o que mudou e descubra os recursos mais recentes do Cadim.', 'steps' => [['selector' => '.changelog-hero', 'title' => 'Atualizações do produto', 'text' => 'Aqui você encontra as melhorias e recursos adicionados ao Cadim.'], ['selector' => '.changelog-list', 'title' => 'Histórico de versões', 'text' => 'Cada entrada mostra a versão, a data e os detalhes da atualização.']]],
    ];
    $helpKey = request()->route()?->getName() === 'accounts' ? 'accounts' : (request()->route()?->getName() ?? 'dashboard');
    $activeHelp = $helpContent[$helpKey] ?? $helpContent['dashboard'];
@endphp
<body class="app-shell" data-onboarding-complete="{{ auth()->user()->onboarding_completed_at ? 'true' : 'false' }}" data-help-tour="{{ auth()->user()->onboarding_completed_at ? $helpKey : 'first-access' }}">
    <div class="min-h-screen lg:flex">
        <aside id="mobile-sidebar" class="app-sidebar" data-mobile-sidebar>
            <a wire:navigate href="{{ route('dashboard') }}" class="brand">
                <img src="{{ asset('logo.svg') }}" alt="Cadim">
            </a>
            <nav class="app-nav">
                <a wire:navigate href="{{ route('dashboard') }}" @class(['app-nav-link','is-active' => request()->routeIs('dashboard')])>Dashboard</a>
                <a wire:navigate href="{{ route('transactions') }}" @class(['app-nav-link','is-active' => request()->routeIs('transactions')])>Transações</a>
                <a wire:navigate href="{{ route('accounts', ['tab' => 'accounts']) }}" @class(['app-nav-link','is-active' => request()->routeIs('accounts') && request('tab', 'accounts') === 'accounts'])>Contas e Cartões</a>
{{--                <a wire:navigate href="{{ route('accounts', ['tab' => 'cards']) }}" @class(['app-nav-link','is-active' => request()->routeIs('accounts') && request('tab') === 'cards'])>Cartões</a>--}}
                <a wire:navigate href="{{ route('categories') }}" @class(['app-nav-link','is-active' => request()->routeIs('categories')])>Categorias</a>
                <a wire:navigate href="{{ route('budgets') }}" @class(['app-nav-link','is-active' => request()->routeIs('budgets')])>Orçamentos e metas</a>
                <a wire:navigate href="{{ route('support') }}" @class(['app-nav-link','is-active' => request()->routeIs('support')])><span class="nav-help-icon">?</span>Central de suporte</a>
                <a wire:navigate href="{{ route('changelog') }}" @class(['app-nav-link','is-active' => request()->routeIs('changelog')])>Novidades</a>
                @if(auth()->user()->isAdmin())<a wire:navigate href="{{ route('admin.dashboard') }}" @class(['app-nav-link','is-active' => request()->routeIs('admin.*')])>Painel admin</a>@endif
            </nav>
            <div class="sidebar-support"><span class="sidebar-support-icon">?</span><div><b>Precisa de ajuda?</b><small>Aprenda em poucos minutos.</small></div><button type="button" data-start-tour aria-label="Iniciar tour guiado">→</button></div>
        </aside>
        <button type="button" class="mobile-menu-backdrop" data-mobile-menu-close aria-label="Fechar menu"></button>
        <section class="min-w-0 flex-1">
            <header class="topbar">
                <div class="topbar-heading"><button type="button" class="mobile-menu-toggle" data-mobile-menu-toggle aria-expanded="false" aria-controls="mobile-sidebar" aria-label="Abrir menu"><i class="bi bi-list" aria-hidden="true"></i></button><p class="text-sm font-semibold text-slate-800">@yield('page-title', 'Dashboard')</p>@isset($dashboardNetWorth)<span class="topbar-net-worth"><small>Patrimônio total</small><strong>R$ {{ number_format($dashboardNetWorth, 2, ',', '.') }}</strong></span>@endisset</div>
                <div class="topbar-actions">
                    <form method="GET" action="{{ route('transactions') }}" class="search-box"><span>⌕</span><input name="search" value="{{ request()->routeIs('transactions') ? request('search') : '' }}" type="search" placeholder="Buscar transação"></form>
                    <button type="button" class="help-button" data-help-open aria-label="Abrir ajuda desta página">?</button>
                    <button type="button" class="theme-toggle" data-theme-toggle onclick="window.toggleTheme(event)" aria-label="Ativar modo escuro" aria-pressed="false"><span class="theme-toggle-track"><span class="theme-toggle-thumb" data-theme-icon>☾</span></span></button>
                    <details class="notifications-menu"><summary class="icon-button relative" aria-label="Notificações"><i class="bi bi-bell" aria-hidden="true"></i>@if(($financialNotifications['count'] ?? 0) > 0)<span class="notification-badge">{{ $financialNotifications['count'] }}</span>@endif</summary><div class="notifications-dropdown"><div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5"><b class="text-xs text-slate-800">Notificações</b>@if(($financialNotifications['count'] ?? 0) > 0)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button type="submit" class="text-[10px] text-emerald-700">Marcar como lidas</button></form>@endif</div>@forelse(($financialNotifications['items'] ?? []) as $notification)<div class="notification-item {{ $notification['read'] ? 'is-read' : '' }}"><a href="{{ $notification['url'] }}" wire:navigate class="block px-3 py-2.5 hover:bg-emerald-50"><b class="block text-xs text-slate-700">{{ $notification['title'] }}</b><small class="mt-0.5 block text-[10px] leading-relaxed text-slate-400">{{ $notification['text'] }}</small></a>@if(!$notification['read'])<form method="POST" action="{{ route('notifications.read', $notification['id']) }}">@csrf<button type="submit" class="notification-mark-read" aria-label="Marcar como lida">✓</button></form>@endif</div>@empty<p class="px-3 py-5 text-center text-xs text-slate-400">Tudo em dia por aqui.</p>@endforelse</div></details>
                    <details class="profile-menu">
                        <summary class="profile-badge" aria-label="Abrir menu do perfil"><span class="profile-avatar">{{ auth()->user()->avatar_icon ?: strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span class="hidden text-xs font-medium sm:inline">{{ auth()->user()->name }}</span><span class="hidden text-slate-400 sm:inline">⌄</span></summary>
                        <div class="profile-dropdown">
                            <div class="profile-dropdown-head"><span class="profile-avatar">{{ auth()->user()->avatar_icon ?: strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></div></div>
                            <a wire:navigate href="{{ route('profile') }}">⚙ <span>Meu perfil</span></a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">↪ <span>Sair</span></button></form>
                        </div>
                    </details>
                </div>
            </header>
            <main class="app-content">{{ $slot }}</main>
        </section>
    </div>
    <div wire:loading.flex class="loading-indicator" aria-live="polite"><span class="loading-dot"></span>Atualizando...</div>
    <div class="help-modal" data-help-modal hidden>
        <div class="help-backdrop" data-help-close></div>
        <section class="help-dialog help-popover" role="dialog" aria-modal="true" aria-labelledby="help-dialog-title">
            <button type="button" class="help-close" data-help-close aria-label="Fechar ajuda">×</button>
            <div class="help-dialog-icon">?</div><p class="eyebrow">GUIA RÁPIDO</p><h2 id="help-dialog-title">{{ $activeHelp['title'] }}</h2><p class="help-dialog-intro">{{ $activeHelp['intro'] }}</p>
            <div class="help-progress"><i data-help-progress></i></div><div class="help-step-count" data-help-count></div><div class="help-step-content"><h3 data-help-step-title></h3><p data-help-step-text></p></div>
            <div class="help-actions"><button type="button" class="help-nav-button" data-help-prev aria-label="Passo anterior">←</button><button type="button" class="help-nav-button" data-help-next aria-label="Próximo passo">→</button></div>
            <script type="application/json" data-help-config>@json($activeHelp['steps'])</script>
        </section>
    </div>
    @livewireScripts
</body>
</html>
