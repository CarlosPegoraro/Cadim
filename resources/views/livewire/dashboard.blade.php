<div class="dashboard-page">
    <div class="dashboard-heading">
        <div><p class="eyebrow">{{ $periodLabel }}</p><h1>Olá, {{ explode(' ', auth()->user()->name)[0] }}!</h1><p>Acompanhe sua vida financeira em um só lugar.</p></div>
        <a wire:navigate href="{{ route('transactions') }}" class="btn-primary">+ Nova transação</a>
    </div>

    <section class="mb-5 rounded-xl border border-[#eaf0ec] bg-white p-4 shadow-[0_5px_22px_rgba(27,61,39,.035)]">
        <div class="flex flex-wrap items-end gap-3">
            <label class="field min-w-40">Período<select wire:model.live="period"><option value="month">Mês</option><option value="year">Ano</option><option value="custom">Personalizado</option></select></label>
            @if($period !== 'custom')<label class="field min-w-44">Referência<input wire:model.live="referenceMonth" type="month"></label>@endif
            @if($period === 'custom')<label class="field min-w-40">De<input wire:model.live="dateFrom" type="date"></label><label class="field min-w-40">Até<input wire:model.live="dateTo" type="date"></label>@endif
            <span class="pb-2 text-xs text-slate-400">{{ $start->format('d/m/Y') }} até {{ $end->format('d/m/Y') }}</span>
        </div>
    </section>

    <div class="dashboard-layout">
        <section class="dashboard-main">
            <div class="summary-grid">
                <article class="metric-card"><div class="metric-icon income">↙</div><p>Receitas do período</p><strong>R$ {{ number_format($income, 2, ',', '.') }}</strong><small><b class="positive">●</b> total de entradas @if($incomeChange !== null) · {{ $incomeChange >= 0 ? '+' : '' }}{{ number_format($incomeChange, 1, ',', '.') }}% vs anterior @endif</small></article>
                <article class="metric-card"><div class="metric-icon expense">↗</div><p>Despesas do período</p><strong>R$ {{ number_format($expense, 2, ',', '.') }}</strong><small><b class="negative">●</b> total de saídas @if($expenseChange !== null) · {{ $expenseChange >= 0 ? '+' : '' }}{{ number_format($expenseChange, 1, ',', '.') }}% vs anterior @endif</small></article>
                <article class="metric-card {{ $finalBalance < 0 ? 'border-rose-200 bg-rose-50/40' : 'border-emerald-200' }}"><div class="metric-icon current-balance">◉</div><p>Saldo final do período</p><strong class="{{ $finalBalance < 0 ? 'text-rose-600' : 'text-emerald-700' }}">R$ {{ number_format($finalBalance, 2, ',', '.') }}</strong><small><b class="{{ $finalBalance < 0 ? 'negative' : 'positive' }}">●</b> receitas menos despesas</small></article>
            </div>

            <article class="panel chart-panel">
                <div class="panel-title"><div><p>Visão financeira</p><h2>Receitas e despesas</h2></div><span class="period-select">{{ $periodLabel }}</span></div>
                @php($max = max(1, $months->max('income'), $months->max('expense')))
                <div class="chart-key"><span><i class="bg-emerald-500"></i>Receitas</span><span><i class="bg-slate-200"></i>Despesas</span></div>
                <div @class(['bar-chart', 'bar-chart-daily' => $period === 'month'])>
                    @foreach($months as $month)
                        <div class="chart-column" title="{{ $month['label'] }}: receitas R$ {{ number_format($month['income'], 2, ',', '.') }}, despesas R$ {{ number_format($month['expense'], 2, ',', '.') }}"><div class="chart-bars"><i class="bar-expense" style="height: {{ max(6, ($month['expense'] / $max) * 150) }}px"></i><i class="bar-income" style="height: {{ max(6, ($month['income'] / $max) * 150) }}px"></i></div><span>{{ $month['label'] }}</span></div>
                    @endforeach
                </div>
            </article>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <article class="panel"><div class="panel-title"><div><p>Onde o dinheiro foi gasto</p><h2>Despesas por categoria</h2></div></div><div class="mt-4 space-y-3">@forelse($expensesByCategory as $category => $amount)<div><div class="flex justify-between text-xs"><span class="font-medium text-slate-600">{{ $category }}</span><strong class="text-slate-700">R$ {{ number_format($amount, 2, ',', '.') }}</strong></div><div class="mt-1 h-1.5 rounded-full bg-slate-100"><i class="block h-full rounded-full bg-rose-300" style="width: {{ $expense > 0 ? min(100, ($amount / $expense) * 100) : 0 }}%"></i></div></div>@empty<p class="empty-copy">Nenhuma despesa no período.</p>@endforelse</div></article>
                <article class="panel"><div class="panel-title"><div><p>Maiores impactos</p><h2>Maiores despesas</h2></div><a wire:navigate href="{{ route('transactions') }}" class="period-select">Ver todas →</a></div><div class="upcoming-list">@forelse($largestExpenses as $item)<div><span class="upcoming-dot expense">↗</span><p><b>{{ $item->description }}</b><small>{{ $item->category?->name ?? 'Sem categoria' }} · {{ $item->due_date->format('d/m/Y') }}</small></p><strong class="amount-expense">R$ {{ number_format($item->amount, 2, ',', '.') }}</strong></div>@empty<p class="empty-copy">Nenhuma despesa no período.</p>@endforelse</div></article>
            </div>

            <article class="panel transactions-panel"><div class="panel-title"><div><p>Movimentações do período</p><h2>Histórico de transações</h2></div><a wire:navigate href="{{ route('transactions') }}" class="period-select">Ver todas →</a></div><div class="table-wrap"><table class="dashboard-table"><thead><tr><th>Transação</th><th>Data</th><th>Categoria</th><th>Valor</th><th>Status</th></tr></thead><tbody>@forelse($recentTransactions as $transaction)<tr><td><b>{{ $transaction->description }}</b><small>{{ $transaction->merchant ?: ($transaction->account?->name ?? ($transaction->creditCard?->name ?? 'Lançamento financeiro')) }}</small></td><td>{{ $transaction->due_date->format('d/m/Y') }}</td><td>{{ $transaction->category?->name ?? 'Sem categoria' }}</td><td class="{{ $transaction->type === 'income' ? 'amount-income' : 'amount-expense' }}">{{ $transaction->type === 'income' ? '+' : '-' }} R$ {{ number_format($transaction->amount, 2, ',', '.') }}</td><td><span class="status-pill {{ $transaction->status }}">{{ $transaction->status === 'settled' ? 'Confirmado' : ($transaction->status === 'canceled' ? 'Cancelado' : 'Pendente') }}</span></td></tr>@empty<tr><td colspan="5" class="empty-row">Ainda não há transações neste período.</td></tr>@endforelse</tbody></table></div></article>
        </section>

        <aside class="dashboard-side">
            <article class="card-widget"><div class="side-heading"><h2>Meus cartões</h2><a wire:navigate href="{{ route('accounts') }}">+ Adicionar</a></div><label class="field mb-4 text-xs">Visualizar<select wire:model.live="selectedCardId"><option value="">Todos os cartões</option>@foreach($cards as $card)<option value="{{ $card->id }}">{{ $card->name }}</option>@endforeach</select></label>@if($primaryCard)<div class="credit-card" style="{{ $selectedCard ? $cardStyle : 'background: linear-gradient(135deg, #2f855a, #14532d); color: #fff;' }}"><span class="card-chip">◈</span><span class="card-wave">⌁</span><p>{{ $selectedCard ? $selectedCard->name : 'Todos os cartões' }}</p><strong>{{ auth()->user()->name }}</strong><div><small>DISPONÍVEL</small><small>{{ $selectedCard ? 'VENCIMENTO' : 'VISÃO GERAL' }}</small></div><div><b>{{ $cardConsolidated['available_limit'] !== null ? 'R$ '.number_format($cardConsolidated['available_limit'], 2, ',', '.') : 'Sem limite' }}</b><b>{{ $selectedCard ? 'Dia '.$selectedCard->due_day : 'Todos' }}</b></div></div>@else<div class="empty-card">Cadastre um cartão para acompanhar seu limite.</div>@endif<a wire:navigate href="{{ route('accounts') }}" class="quick-action">▣ <span>Gerenciar cartões</span>→</a></article>
            <article class="side-panel"><div class="side-heading"><h2>Limite disponível</h2><span>•••</span></div><p class="limit-value">R$ {{ number_format($cardConsolidated['available_limit'], 2, ',', '.') }}</p><p class="limit-subtitle">R$ {{ number_format($cardConsolidated['used_limit'], 2, ',', '.') }} comprometidos</p><div class="progress-track"><i style="width: {{ $cardUtilization }}%"></i></div></article>
        </aside>
    </div>
</div>
