<?php

namespace App\Livewire;

use App\Models\TransactionOccurrence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HouseholdFinance extends Component
{
    public string $section = 'home';

    public string $month = '';

    public bool $showForm = false;

    public string $description = '';

    public string $amount = '';

    public string $type = 'expense';

    public string $date = '';

    public string $accountId = '';

    public string $categoryId = '';

    public string $notes = '';

    public string $day = '5';

    public string $endDate = '';

    public string $installments = '2';

    public string $total = '';

    public string $accountName = '';

    public string $accountType = 'checking';

    public string $institution = '';

    public string $dueDay = '';

    public string $closingDay = '';

    public ?int $editingId = null;

    public ?int $editingSeriesId = null;

    public function mount(string $section = 'home'): void
    {
        $this->section = $section;
        $this->month = now()->format('Y-m');
        $this->date = now()->toDateString();
    }

    public function moveMonth(int $offset): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->addMonthsNoOverflow($offset)->format('Y-m');
    }

    public function newForm(): void
    {
        $this->reset('editingId', 'editingSeriesId', 'description', 'amount', 'accountId', 'categoryId', 'notes', 'total', 'endDate');
        $this->type = 'expense';
        $this->date = now()->toDateString();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->reset('editingId', 'editingSeriesId');
        $this->resetValidation();
    }

    public function create(): void
    {
        if ($this->section === 'accounts') {
            $this->validate(['accountName' => ['required', 'string', 'max:80'], 'accountType' => ['required', 'in:checking,credit,cash'], 'institution' => ['nullable', 'string', 'max:80'], 'dueDay' => ['nullable', 'integer', 'between:1,31'], 'closingDay' => ['nullable', 'integer', 'between:1,31']]);
            auth()->user()->accounts()->create(['name' => $this->accountName, 'type' => $this->accountType, 'institution' => $this->institution ?: null, 'due_day' => $this->dueDay ?: null, 'closing_day' => $this->closingDay ?: null]);
            $this->reset('accountName', 'institution', 'dueDay', 'closingDay');
            $this->showForm = false;
            session()->flash('message', 'Conta adicionada.');

            return;
        }
        $rules = [
            'description' => ['required', 'string', 'max:120'], 'type' => ['required', 'in:income,expense'],
            'date' => ['required', 'date'],
            'accountId' => ['nullable', 'integer'], 'categoryId' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
        if ($this->section === 'recurring') {
            $rules['amount'] = ['required', 'regex:/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:[,.]\d{1,2})?$/', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($this->moneyToCents((string) $value) <= 0) {
                    $fail('O valor deve ser maior que zero.');
                }
            }];
            $rules['day'] = ['required', 'integer', 'between:1,31'];
            $rules['date'] = ['required', 'date'];
            $rules['endDate'] = ['nullable', 'date', 'after_or_equal:date'];
        }
        if ($this->section === 'installments') {
            $rules['amount'] = ['nullable'];
            $rules['total'] = ['required', 'regex:/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:[,.]\d{1,2})?$/', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($this->moneyToCents((string) $value) <= 0) {
                    $fail('O valor deve ser maior que zero.');
                }
            }];
            $rules['installments'] = ['required', 'integer', 'between:2,120'];
        }
        if (! in_array($this->section, ['recurring', 'installments'], true)) {
            $rules['amount'] = ['required', 'regex:/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:[,.]\d{1,2})?$/', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($this->moneyToCents((string) $value) <= 0) {
                    $fail('O valor deve ser maior que zero.');
                }
            }];
        }
        $this->validate($rules, [], [
            'description' => 'descrição', 'amount' => 'valor', 'date' => 'data', 'day' => 'dia do mês',
            'total' => 'valor total', 'installments' => 'quantidade de parcelas',
        ]);
        $user = auth()->user();
        if ($this->accountId !== '' && ! $user->accounts()->whereKey($this->accountId)->exists()) {
            $this->addError('accountId', 'Selecione uma conta válida.');

            return;
        }
        if ($this->categoryId !== '' && ! $user->categories()->whereKey($this->categoryId)->where('type', $this->type)->exists()) {
            $this->addError('categoryId', 'Selecione uma categoria válida.');

            return;
        }

        DB::transaction(function () use ($user): void {
            $recurrence = match ($this->section) {
                'recurring' => 'monthly', 'installments' => 'installment', default => 'one_time'
            };
            $base = [
                'type' => $this->type, 'amount' => $this->moneyToDecimal($this->amount), 'description' => $this->description,
                'category_id' => $this->categoryId ?: null, 'financial_account_id' => $this->accountId ?: null,
                'recurrence' => $recurrence, 'starts_on' => $this->date,
                'recurrence_day' => $recurrence === 'monthly' ? (int) $this->day : null,
                'ends_on' => $this->endDate ?: null, 'installments' => null, 'is_active' => true,
            ];
            if ($recurrence === 'installment') {
                $count = (int) $this->installments;
                $totalCents = $this->moneyToCents($this->total);
                $base['amount'] = $this->centsToDecimal(intdiv($totalCents, $count));
                $base['installments'] = $count;
            }
            $series = $user->transactionSeries()->create($base);
            $limit = $recurrence === 'installment'
                ? Carbon::parse($this->date)->addMonthsNoOverflow((int) $this->installments - 1)
                : ($recurrence === 'monthly' ? min(Carbon::parse($this->endDate ?: now()->addMonths(12)), now()->addMonths(12)) : Carbon::parse($this->date));
            $start = Carbon::parse($this->date);
            $initialDay = (int) $start->day;
            $remainder = $recurrence === 'installment' ? $this->moneyToCents($this->total) % (int) $this->installments : 0;
            for ($i = 0, $cursor = $start->copy()->startOfMonth(); $cursor->lte($limit); $cursor->addMonthNoOverflow(), $i++) {
                if ($recurrence === 'one_time' && $i > 0) {
                    break;
                }
                if ($recurrence === 'installment' && $i >= (int) $this->installments) {
                    break;
                }
                $date = $cursor->copy()->day(min($initialDay, $cursor->daysInMonth));
                if ($recurrence === 'monthly') {
                    $date->day(min((int) $this->day, $cursor->daysInMonth));
                }
                $cents = $this->decimalToCents($base['amount']) + ($i < $remainder ? 1 : 0);
                TransactionOccurrence::firstOrCreate(
                    ['transaction_series_id' => $series->id, 'due_date' => $date->toDateString()],
                    ['user_id' => $user->id, 'category_id' => $base['category_id'], 'financial_account_id' => $base['financial_account_id'], 'type' => $this->type,
                        'amount' => $this->centsToDecimal($cents), 'description' => $this->description, 'notes' => $this->notes ?: null,
                        'due_date' => $date, 'competence_month' => $date->copy()->startOfMonth(), 'status' => 'pending', 'installment_number' => $recurrence === 'installment' ? $i + 1 : null]
                );
            }
        });
        $this->reset('description', 'amount', 'notes', 'accountId', 'categoryId', 'endDate', 'total');
        $this->date = now()->toDateString();
        $this->showForm = false;
        session()->flash('message', 'Salvo com sucesso.');
    }

    public function editOccurrence(int $id): void
    {
        $item = auth()->user()->transactions()->findOrFail($id);
        $this->editingId = $item->id;
        $this->description = $item->description;
        $this->amount = $item->amount;
        $this->type = $item->type;
        $this->date = $item->due_date->toDateString();
        $this->accountId = (string) ($item->financial_account_id ?? '');
        $this->categoryId = (string) ($item->category_id ?? '');
        $this->notes = $item->notes ?? '';
        $this->showForm = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'description' => ['required', 'string', 'max:120'], 'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'regex:/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:[,.]\d{1,2})?$/', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($this->moneyToCents((string) $value) <= 0) {
                    $fail('O valor deve ser maior que zero.');
                }
            }],
            'date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $user = auth()->user();
        if ($this->accountId !== '' && ! $user->accounts()->whereKey($this->accountId)->exists()) {
            $this->addError('accountId', 'Selecione uma conta válida.');

            return;
        }
        if ($this->categoryId !== '' && ! $user->categories()->whereKey($this->categoryId)->where('type', $this->type)->exists()) {
            $this->addError('categoryId', 'Selecione uma categoria válida.');

            return;
        }
        $user->transactions()->findOrFail($this->editingId)->update([
            'description' => $this->description, 'amount' => $this->moneyToDecimal($this->amount), 'type' => $this->type,
            'due_date' => $this->date, 'competence_month' => Carbon::parse($this->date)->startOfMonth(),
            'financial_account_id' => $this->accountId ?: null, 'category_id' => $this->categoryId ?: null, 'notes' => $this->notes ?: null,
        ]);
        $this->editingId = null;
        $this->showForm = false;
        session()->flash('message', 'Lançamento atualizado.');
    }

    public function editFutureSeries(int $id): void
    {
        $series = auth()->user()->transactionSeries()->findOrFail($id);
        $this->editingSeriesId = $series->id;
        $this->description = $series->description;
        $this->amount = $series->amount;
        $this->type = $series->type;
        $this->day = (string) ($series->recurrence_day ?: $series->starts_on->day);
        $this->showForm = true;
    }

    public function saveFutureSeries(): void
    {
        $this->validate([
            'description' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'regex:/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:[,.]\d{1,2})?$/', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($this->moneyToCents((string) $value) <= 0) {
                    $fail('O valor deve ser maior que zero.');
                }
            }],
            'day' => ['required', 'integer', 'between:1,31'],
        ]);
        $series = auth()->user()->transactionSeries()->findOrFail($this->editingSeriesId);
        DB::transaction(function () use ($series): void {
            $amount = $this->moneyToDecimal($this->amount);
            $typeChanged = $series->type !== $this->type;
            $series->update(['description' => $this->description, 'amount' => $amount, 'type' => $this->type] + ($typeChanged ? ['category_id' => null] : []) + ($series->recurrence === 'monthly' ? ['recurrence_day' => $this->day] : []));
            $future = $series->occurrences()->where('status', 'pending')->whereDate('due_date', '>=', today())->get();
            foreach ($future as $occurrence) {
                $changes = ['description' => $this->description, 'amount' => $amount, 'type' => $this->type];
                if ($typeChanged) {
                    $changes['category_id'] = null;
                }
                if ($series->recurrence === 'monthly') {
                    $dueDate = $occurrence->due_date->copy()->startOfMonth()->day(min((int) $this->day, $occurrence->due_date->daysInMonth));
                    $changes['due_date'] = $dueDate;
                    $changes['competence_month'] = $dueDate->copy()->startOfMonth();
                }
                $occurrence->update($changes);
            }
        });
        $this->editingSeriesId = null;
        $this->showForm = false;
        session()->flash('message', 'Valor padrão atualizado para as próximas competências.');
    }

    public function endSeries(int $id): void
    {
        $series = auth()->user()->transactionSeries()->findOrFail($id);
        $series->update(['is_active' => false]);
        $series->occurrences()->where('status', 'pending')->whereDate('due_date', '>', today())->update(['status' => 'canceled']);
        session()->flash('message', $series->recurrence === 'monthly' ? 'Recorrência desativada; histórico preservado.' : 'Parcelamento encerrado. Parcelas já pagas foram preservadas.');
    }

    public function toggle(int $id): void
    {
        $item = auth()->user()->transactions()->findOrFail($id);
        $item->update(['status' => $item->status === 'settled' ? 'pending' : 'settled', 'settled_at' => $item->status === 'settled' ? null : now()->toDateString()]);
        session()->flash('message', $item->status === 'settled' ? 'Pagamento desfeito.' : 'Pagamento confirmado.');
    }

    public function delete(int $id): void
    {
        $item = auth()->user()->transactions()->with('series')->findOrFail($id);
        if ($item->series?->recurrence === 'monthly') {
            $item->update(['status' => 'canceled']);
        } else {
            $item->delete();
        }
        session()->flash('message', 'Lançamento excluído.');
    }

    public function render()
    {
        $user = auth()->user();
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        foreach ($user->transactionSeries()->where('recurrence', 'monthly')->where('is_active', true)->get() as $recurring) {
            $date = $start->copy()->day(min((int) ($recurring->recurrence_day ?: $recurring->starts_on->day), $start->daysInMonth));
            if ($date->gte($recurring->starts_on) && (! $recurring->ends_on || $date->lte($recurring->ends_on))) {
                $existing = TransactionOccurrence::query()->where('transaction_series_id', $recurring->id)->whereDate('due_date', $date)->exists();
                if (! $existing) {
                    TransactionOccurrence::create(
                        ['transaction_series_id' => $recurring->id, 'due_date' => $date,
                            'user_id' => $user->id, 'category_id' => $recurring->category_id, 'financial_account_id' => $recurring->financial_account_id,
                            'type' => $recurring->type, 'amount' => $recurring->amount, 'description' => $recurring->description,
                            'competence_month' => $date->copy()->startOfMonth(), 'status' => 'pending']
                    );
                }
            }
        }
        $items = $user->transactions()->with(['account', 'category', 'series'])->where('status', '!=', 'canceled')->whereBetween('due_date', [$start, $start->copy()->endOfMonth()])->orderBy('due_date')->get();
        $creditInvoices = $user->accounts()->where('type', 'credit')->get()->mapWithKeys(function ($account) use ($start): array {
            $cents = $account->transactions()->where('type', 'expense')->where('status', '!=', 'canceled')->whereBetween('due_date', [$start, $start->copy()->endOfMonth()])->get()->sum(fn ($item) => $this->decimalToCents($item->amount));

            return [$account->id => $cents];
        });
        $income = $items->where('type', 'income')->sum(fn ($item) => $this->decimalToCents($item->amount));
        $expense = $items->where('type', 'expense')->sum(fn ($item) => $this->decimalToCents($item->amount));
        $query = $user->transactionSeries()->withCount(['occurrences as paid_count' => fn ($q) => $q->where('status', 'settled'), 'occurrences as pending_count' => fn ($q) => $q->where('status', 'pending')])->withSum(['occurrences as remaining_amount' => fn ($q) => $q->where('status', 'pending')], 'amount')->latest();
        if ($this->section === 'recurring') {
            $query->where('recurrence', 'monthly');
        }
        if ($this->section === 'installments') {
            $query->where('recurrence', 'installment');
        }
        $series = $query->get();

        return view('livewire.household-finance', [
            'items' => $items, 'accounts' => $user->accounts()->where('is_archived', false)->orderBy('name')->get(),
            'categories' => $user->categories()->where('is_archived', false)->orderBy('name')->get(), 'series' => $series,
            'creditInvoices' => $creditInvoices,
            'income' => $income, 'expense' => $expense, 'balance' => $income - $expense,
            'pending' => $items->where('type', 'expense')->where('status', 'pending')->sum(fn ($i) => $this->decimalToCents($i->amount)),
            'overdue' => $items->where('type', 'expense')->where('status', 'pending')->filter(fn ($i) => $i->due_date->lt(today()))->count(),
            'monthLabel' => $start->locale('pt_BR')->translatedFormat('F Y'),
        ])->layout('layouts.app');
    }

    private function moneyToDecimal(string $value): string
    {
        $normalized = str_contains($value, ',')
            ? str_replace(',', '.', str_replace('.', '', trim($value)))
            : trim($value);
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');

        return $this->centsToDecimal(((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0'));
    }

    private function moneyToCents(string $value): int
    {
        return $this->decimalToCents($this->moneyToDecimal($value));
    }

    private function decimalToCents(string|int $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function centsToDecimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
