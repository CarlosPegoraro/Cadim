<div class="mx-auto max-w-6xl">
    @php
        $titles = ['home' => 'Seu mês, sem complicação', 'transactions' => 'Lançamentos', 'recurring' => 'Contas recorrentes', 'installments' => 'Compras parceladas', 'accounts' => 'Contas e cartões'];
        $money = fn ($cents) => 'R$ '.number_format($cents / 100, 2, ',', '.');
        $toCents = function ($amount) { [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '0'); return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0'); };
        $forms = ['home' => 'Novo lançamento', 'transactions' => 'Novo lançamento', 'recurring' => 'Nova recorrência', 'installments' => 'Nova compra parcelada', 'accounts' => 'Nova conta'];
    @endphp
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-700">FINANÇAS DA CASA</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $titles[$section] ?? 'Lançamentos' }}</h1><p class="mt-1 text-sm text-slate-500">Tudo que entra e sai, em um só lugar.</p></div>
        <button wire:click="newForm" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">+ {{ $forms[$section] ?? 'Novo lançamento' }}</button>
    </div>
    @if (session('message')) <div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('message') }}</div> @endif

    @if(in_array($section, ['home','transactions']))
        <div class="mt-7 flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3">
            <button wire:click="moveMonth(-1)" class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-50" aria-label="Mês anterior">←</button>
            <div class="text-center"><div class="font-semibold capitalize text-slate-800">{{ $monthLabel }}</div><button wire:click="$set('month', '{{ now()->format('Y-m') }}')" class="text-xs text-emerald-700">Ir para este mês</button></div>
            <button wire:click="moveMonth(1)" class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-50" aria-label="Próximo mês">→</button>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach([['Receitas previstas',$money($income),'text-emerald-700'],['Despesas previstas',$money($expense),'text-rose-600'],['Saldo previsto',$money($balance),'text-slate-900']] as [$label,$value,$tone])
                <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-2 text-lg font-semibold {{ $tone }}">{{ $value }}</p></div>
            @endforeach
        </div>
        @if($overdue)<p class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $overdue }} {{ $overdue === 1 ? 'despesa vencida' : 'despesas vencidas' }} aguardando pagamento.</p>@endif
        <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h2 class="font-semibold">Lançamentos do mês</h2><span class="text-xs text-slate-400">{{ $items->count() }} itens</span></div>
            <div class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <article class="flex items-center gap-3 px-4 py-4 sm:px-5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $item->type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-orange-50 text-orange-700' }}">{{ $item->type === 'income' ? '↙' : '↗' }}</div>
                        <div class="min-w-0 flex-1"><div class="truncate text-sm font-medium text-slate-800">{{ $item->description }} @if($item->installment_number)<span class="ml-1 text-xs text-slate-400">{{ $item->installment_number }}/{{ $item->series?->installments }}</span>@endif</div><div class="mt-1 text-xs text-slate-500">{{ $item->due_date->format('d/m/Y') }}@if($item->account) · {{ $item->account->name }}@endif</div></div>
                        <div class="text-right"><div class="text-sm font-semibold {{ $item->type === 'income' ? 'text-emerald-700' : 'text-slate-800' }}">{{ $money($toCents($item->amount)) }}</div><div class="mt-1 text-xs {{ $item->status === 'settled' ? 'text-emerald-700' : ($item->due_date->lt(today()) ? 'text-rose-600' : 'text-amber-700') }}">{{ $item->status === 'settled' ? ($item->type === 'income' ? 'Recebido' : 'Pago') : ($item->due_date->lt(today()) ? 'Atrasado' : 'Pendente') }}</div></div>
                        <button wire:click="editOccurrence({{ $item->id }})" class="ml-1 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 hover:bg-slate-50" aria-label="Editar">✎</button>
                        <button wire:click="toggle({{ $item->id }})" class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 hover:bg-slate-50" title="{{ $item->status === 'settled' ? 'Desfazer' : 'Confirmar' }}">{{ $item->status === 'settled' ? '↶' : '✓' }}</button>
                        <button wire:click="delete({{ $item->id }})" wire:confirm="Excluir este lançamento?" class="rounded-lg px-2 py-2 text-xs text-slate-400 hover:text-rose-600" aria-label="Excluir">×</button>
                    </article>
                @empty <div class="px-5 py-14 text-center"><div class="text-3xl">☀</div><p class="mt-3 font-medium text-slate-700">Seu mês está livre por enquanto</p><p class="mt-1 text-sm text-slate-400">Adicione um lançamento ou crie uma conta recorrente.</p></div> @endforelse
            </div>
        </section>
    @elseif($section === 'accounts')
        <section class="mt-7 rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-5 py-4 font-semibold">Suas contas</div>
            @forelse($accounts as $account)<div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 last:border-0"><div><span class="font-medium">{{ $account->name }}</span><div class="mt-1 text-xs text-slate-400">{{ $account->institution }} @if($account->type === 'credit' && $account->due_day)· Vence dia {{ $account->due_day }}@endif</div>@if($account->type === 'credit')<div class="mt-2 text-xs text-slate-500">Fatura deste mês <strong class="ml-1 text-slate-800">{{ $money($creditInvoices[$account->id] ?? 0) }}</strong></div>@endif</div><span class="text-xs text-slate-500">{{ ['checking' => 'Conta bancária', 'credit' => 'Cartão de crédito', 'cash' => 'Carteira'][$account->type] ?? 'Outro' }}</span></div>@empty<div class="px-5 py-12 text-center text-sm text-slate-400">Cadastre uma conta ou cartão para associar aos seus lançamentos.</div>@endforelse
        </section>
    @else
        <section class="mt-7 grid gap-3 md:grid-cols-2">
            @forelse($series as $entry)
                <article class="rounded-2xl border border-slate-200 bg-white p-5"><div class="flex items-start justify-between"><div><h2 class="font-semibold">{{ $entry->description }}</h2><p class="mt-1 text-sm text-slate-500">{{ $entry->type === 'income' ? 'Receita' : 'Despesa' }} · {{ $entry->recurrence === 'monthly' ? 'Todo dia '.($entry->recurrence_day ?: Carbon\Carbon::parse($entry->starts_on)->format('d')) : $entry->installments.' parcelas' }}</p></div><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs text-emerald-700">{{ $entry->is_active ? 'Ativa' : 'Encerrada' }}</span></div><div class="mt-5 flex items-end justify-between"><strong class="text-xl">{{ $money($toCents($entry->amount)) }}{{ $entry->recurrence === 'monthly' ? '/mês' : '' }}</strong>@if($entry->installments)<div class="text-right text-xs text-slate-500">{{ $entry->paid_count }} pagas · {{ $entry->pending_count }} pendentes<br>Restante: {{ $money($toCents($entry->remaining_amount ?? 0)) }}</div>@endif</div><div class="mt-4 flex gap-2 border-t border-slate-100 pt-3">@if($entry->is_active)<button wire:click="editFutureSeries({{ $entry->id }})" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 transition hover:border-emerald-300 hover:bg-emerald-100"><span aria-hidden="true">✎</span>{{ $entry->recurrence === 'monthly' ? 'Ajustar' : 'Ajustar parcelas' }}</button><button wire:click="endSeries({{ $entry->id }})" wire:confirm="{{ $entry->recurrence === 'monthly' ? 'Desativar esta recorrência? O histórico será mantido.' : 'Encerrar este parcelamento e cancelar parcelas futuras pendentes?' }}" class="ml-auto text-xs text-slate-400 hover:text-rose-600">{{ $entry->recurrence === 'monthly' ? 'Desativar' : 'Encerrar' }}</button>@endif</div></article>
            @empty <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center text-sm text-slate-400 md:col-span-2">Nada cadastrado ainda. Use o botão acima para começar.</div> @endforelse
        </section>
    @endif

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/35 p-0 sm:items-center sm:p-5" wire:click.self="$set('showForm', false)">
            <section class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-3xl bg-white p-5 shadow-xl sm:rounded-3xl sm:p-7">
                <div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">{{ $editingId || $editingSeriesId ? 'EDIÇÃO' : 'NOVO REGISTRO' }}</p><h2 class="mt-1 text-xl font-semibold">{{ $editingId ? 'Editar lançamento' : ($editingSeriesId ? 'Atualizar próximos valores' : ($forms[$section] ?? 'Novo lançamento')) }}</h2></div><button wire:click="closeForm" class="text-2xl leading-none text-slate-400">×</button></div>
                <form wire:submit="{{ $editingId ? 'saveEdit' : ($editingSeriesId ? 'saveFutureSeries' : 'create') }}" class="mt-5 space-y-4">
                    @if($editingSeriesId)
                        <label class="block text-sm font-medium">Descrição<input wire:model="description" class="mt-1.5 w-full rounded-xl border-slate-200"></label>
                        @if($section === 'recurring')<label class="block text-sm font-medium">Tipo<select wire:model="type" class="mt-1.5 w-full rounded-xl border-slate-200"><option value="expense">Despesa</option><option value="income">Receita</option></select></label>@endif
                        <label class="block text-sm font-medium">Novo valor padrão (R$)<input wire:model="amount" inputmode="decimal" class="mt-1.5 w-full rounded-xl border-slate-200"></label>
                        @if($section === 'recurring')<label class="block text-sm font-medium">Dia do mês<input wire:model="day" type="number" min="1" max="31" class="mt-1.5 w-full rounded-xl border-slate-200"></label>@endif
                    @elseif($section === 'accounts')
                        <label class="block text-sm font-medium">Nome<input wire:model="accountName" class="mt-1.5 w-full rounded-xl border-slate-200" placeholder="Ex.: Nubank"></label>@error('accountName')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-2 gap-3"><label class="block text-sm font-medium">Tipo<select wire:model.live="accountType" class="mt-1.5 w-full rounded-xl border-slate-200"><option value="checking">Conta bancária</option><option value="credit">Cartão de crédito</option><option value="cash">Carteira ou outros</option></select></label><label class="block text-sm font-medium">Instituição<input wire:model="institution" class="mt-1.5 w-full rounded-xl border-slate-200" placeholder="Opcional"></label></div>
                        @if($accountType === 'credit')<div class="grid grid-cols-2 gap-3"><label class="block text-sm font-medium">Vencimento da fatura<input wire:model="dueDay" type="number" min="1" max="31" class="mt-1.5 w-full rounded-xl border-slate-200"></label><label class="block text-sm font-medium">Fechamento (opcional)<input wire:model="closingDay" type="number" min="1" max="31" class="mt-1.5 w-full rounded-xl border-slate-200"></label></div>@endif
                    @else
                        <label class="block text-sm font-medium">Descrição<input wire:model="description" class="mt-1.5 w-full rounded-xl border-slate-200" placeholder="Ex.: Aluguel"></label>@error('description')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        @if($section !== 'installments')<div class="grid grid-cols-2 gap-3"><label class="block text-sm font-medium">Tipo<select wire:model="type" class="mt-1.5 w-full rounded-xl border-slate-200"><option value="expense">Despesa</option><option value="income">Receita</option></select></label><label class="block text-sm font-medium">Valor (R$)<input wire:model="amount" inputmode="decimal" class="mt-1.5 w-full rounded-xl border-slate-200" placeholder="0,00"></label></div>@error('amount')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror @endif
                        @if($section === 'installments')<div class="grid grid-cols-2 gap-3"><label class="block text-sm font-medium">Valor total (R$)<input wire:model="total" inputmode="decimal" class="mt-1.5 w-full rounded-xl border-slate-200" placeholder="0,00"></label><label class="block text-sm font-medium">Parcelas<input wire:model="installments" type="number" min="2" max="120" class="mt-1.5 w-full rounded-xl border-slate-200"></label></div><input type="hidden" wire:model="type" value="expense">@error('total')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror @else @endif
                        @if($section === 'recurring')<div class="grid grid-cols-2 gap-3"><label class="block text-sm font-medium">Dia do mês<input wire:model="day" type="number" min="1" max="31" class="mt-1.5 w-full rounded-xl border-slate-200"></label><label class="block text-sm font-medium">Começa em<input wire:model="date" type="date" class="mt-1.5 w-full rounded-xl border-slate-200"></label></div><label class="block text-sm font-medium">Termina em (opcional)<input wire:model="endDate" type="date" class="mt-1.5 w-full rounded-xl border-slate-200"></label>@else<label class="block text-sm font-medium">{{ $section === 'installments' ? 'Vencimento da primeira parcela' : 'Data' }}<input wire:model="date" type="date" class="mt-1.5 w-full rounded-xl border-slate-200"></label>@endif
                        @error('date')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-2 gap-3"><label class="block text-sm font-medium">Conta / cartão<select wire:model="accountId" class="mt-1.5 w-full rounded-xl border-slate-200"><option value="">Sem conta</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label><label class="block text-sm font-medium">Categoria<select wire:model="categoryId" class="mt-1.5 w-full rounded-xl border-slate-200"><option value="">Sem categoria</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label></div>
                        <label class="block text-sm font-medium">Observação (opcional)<textarea wire:model="notes" rows="2" class="mt-1.5 w-full rounded-xl border-slate-200"></textarea></label>
                    @endif
                    <button class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800">Salvar</button>
                </form>
            </section>
        </div>
    @endif
</div>
