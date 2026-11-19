<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Casa em Dia</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f6f8f6] text-slate-800 antialiased">
<div class="min-h-screen lg:flex">
    <aside class="w-full border-b border-slate-200 bg-white px-5 py-4 lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-r lg:px-6">
        <a href="{{ route('dashboard') }}" wire:navigate class="text-xl font-bold tracking-tight text-emerald-800">casa<span class="text-emerald-500">.</span>em dia</a>
        <nav class="mt-6 flex gap-2 overflow-x-auto lg:flex-col">
            @foreach([['dashboard','Início'],['transactions','Lançamentos'],['recurring','Recorrentes'],['installments','Parcelamentos'],['accounts','Contas']] as [$route,$label])
                <a href="{{ route($route) }}" wire:navigate @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium transition', 'bg-emerald-50 text-emerald-800' => request()->routeIs($route), 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => !request()->routeIs($route)])>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="mt-8 hidden rounded-2xl bg-emerald-50 p-4 text-xs leading-5 text-emerald-900 lg:block">Um lugar simples para cuidar do dinheiro da casa.</div>
        <div class="mt-5 hidden items-center justify-between text-sm lg:flex"><span class="truncate text-slate-500">{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="text-slate-400 hover:text-slate-700">Sair</button></form></div>
    </aside>
    <main class="min-w-0 flex-1 px-4 py-6 sm:px-8 lg:px-10 lg:py-9">{{ $slot }}</main>
</div>
@livewireScripts
</body>
</html>
