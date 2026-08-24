<?php

namespace App\Livewire;

use App\Services\AccountBalanceService;
use App\Services\CreditCardBalanceService;
use App\Services\OnboardingProgressService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public string $period = 'month';

    public string $referenceMonth = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $selectedCardId = '';

    public function mount(): void
    {
        $this->referenceMonth = now()->format('Y-m');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->endOfMonth()->toDateString();
    }

    public function updatedPeriod(): void
    {
        if ($this->period === 'custom' && ! $this->dateFrom) {
            $this->dateFrom = now()->startOfMonth()->toDateString();
            $this->dateTo = now()->endOfMonth()->toDateString();
        }
    }

    public function render(CreditCardBalanceService $cardBalances, AccountBalanceService $accountBalances, OnboardingProgressService $onboarding)
    {
        [$start, $end] = $this->periodRange();
        [$comparisonStart, $comparisonEnd] = $this->comparisonRange($start, $end);
        $user = auth()->user();
        $onboardingSteps = $onboarding->summary($user);
        $cards = $user->creditCards()->where('is_archived', false)->orderBy('name')->get();
        $selectedCard = $this->selectedCardId !== ''
            ? $cards->firstWhere('id', (int) $this->selectedCardId)
            : null;
        if ($this->selectedCardId !== '' && ! $selectedCard) {
            $this->selectedCardId = '';
        }
        $transactions = $user->transactions()->where('status', '!=', 'canceled')->where(function ($query) {
            $query->whereNull('financial_account_id')
                ->orWhereHas('account', fn ($accountQuery) => $accountQuery->whereNotIn('type', ['investments', 'savings']));
        });
        if ($selectedCard) {
            $transactions->where('credit_card_id', $selectedCard->id);
        }
        $periodTransactions = (clone $transactions)->with(['category', 'account', 'creditCard'])->whereBetween('due_date', [$start, $end])->get();
        $comparisonTransactions = (clone $transactions)->whereBetween('due_date', [$comparisonStart, $comparisonEnd]);
        $income = (float) (clone $periodTransactions)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $periodTransactions)->where('type', 'expense')->sum('amount');
        $settledIncome = (float) (clone $periodTransactions)->where('type', 'income')->where('status', 'settled')->sum('amount');
        $settledExpense = (float) (clone $periodTransactions)->where('type', 'expense')->where('status', 'settled')->sum('amount');
        $comparisonIncome = (float) (clone $comparisonTransactions)->where('type', 'income')->sum('amount');
        $comparisonExpense = (float) (clone $comparisonTransactions)->where('type', 'expense')->sum('amount');
        $months = $this->chartMonths($start, $end, $transactions);
        $expensesByCategory = $periodTransactions
            ->where('type', 'expense')
            ->groupBy(fn ($transaction): string => $transaction->category ? $transaction->category->name : 'Sem categoria')
            ->map(fn (Collection $items): float => (float) $items->sum('amount'))
            ->sortDesc()
            ->take(5);
        $largestExpenses = $periodTransactions->where('type', 'expense')->sortByDesc('amount')->take(5);
        $upcomingQuery = $user->transactions()->with(['category', 'creditCard'])->where('status', 'pending')->whereBetween('due_date', [today(), today()->addDays(7)])->where(function ($query) {
            $query->whereNull('financial_account_id')
                ->orWhereHas('account', fn ($accountQuery) => $accountQuery->whereNotIn('type', ['investments', 'savings']));
        });
        if ($selectedCard) {
            $upcomingQuery->where('credit_card_id', $selectedCard->id);
        }
        $upcoming = $upcomingQuery->orderBy('due_date')->limit(5)->get();
        $recentTransactions = $periodTransactions->sortByDesc('due_date')->take(5);
        $primaryCard = $selectedCard ?: $cards->sortByDesc('limit')->first();
        $cardSummary = $cardBalances->summarizeForUser($user);
        $cardConsolidated = $selectedCard
            ? ($cardSummary['cards'][$selectedCard->id] ?? $cardSummary['consolidated'])
            : $cardSummary['consolidated'];
        $primaryCardSummary = $selectedCard
            ? ($cardSummary['cards'][$selectedCard->id] ?? null)
            : null;
        $cardUtilization = $cardConsolidated['limit'] > 0
            ? min(100, max(0, ($cardConsolidated['used_limit'] / $cardConsolidated['limit']) * 100))
            : 0;
        $accountSummary = $accountBalances->summarizeForUser($user, $end, ['checking']);
        $currentBalance = $accountSummary['consolidated']['realized_balance'];
        $projectedBalance = $accountSummary['consolidated']['projected_balance'];
        $netWorth = $accountSummary['net_worth']['realized_balance'];

        return view('livewire.dashboard', [
            'start' => $start,
            'end' => $end,
            'periodLabel' => $this->periodLabel($start, $end),
            'income' => $income,
            'expense' => $expense,
            'currentBalance' => $currentBalance,
            'projectedBalance' => $projectedBalance,
            'netWorth' => $netWorth,
            'settledIncome' => $settledIncome,
            'settledExpense' => $settledExpense,
            'incomeChange' => $this->percentageChange($income, $comparisonIncome),
            'expenseChange' => $this->percentageChange($expense, $comparisonExpense),
            'months' => $months,
            'expensesByCategory' => $expensesByCategory,
            'largestExpenses' => $largestExpenses,
            'upcoming' => $upcoming,
            'recentTransactions' => $recentTransactions,
            'primaryCard' => $primaryCard,
            'primaryCardSummary' => $primaryCardSummary,
            'selectedCard' => $selectedCard,
            'selectedCardId' => $this->selectedCardId,
            'cards' => $cards,
            'cardStyle' => $this->cardStyle($selectedCard?->brand),
            'cardConsolidated' => $cardConsolidated,
            'cardUtilization' => $cardUtilization,
            'onboardingSteps' => $onboardingSteps,
        ])->layout('layouts.app', ['dashboardNetWorth' => $netWorth]);
    }

    private function cardStyle(?string $brand): string
    {
        return match (strtolower((string) $brand)) {
            'visa' => 'background: linear-gradient(135deg, #1d4ed8, #1e1b4b); color: #fff;',
            'mastercard' => 'background: linear-gradient(135deg, #f97316, #881337); color: #fff;',
            'american express', 'amex' => 'background: linear-gradient(135deg, #0891b2, #0f172a); color: #fff;',
            'elo' => 'background: linear-gradient(135deg, #eab308, #9a3412); color: #fff;',
            default => 'background: linear-gradient(135deg, #2f855a, #14532d); color: #fff;',
        };
    }

    /** @return array{Carbon, Carbon} */
    private function periodRange(): array
    {
        if ($this->period === 'year') {
            $year = (int) substr($this->referenceMonth, 0, 4);
            $date = Carbon::create($year, 1, 1);

            return [$date->copy()->startOfYear(), $date->copy()->endOfYear()];
        }

        if ($this->period === 'custom') {
            $start = Carbon::parse($this->dateFrom ?: now()->startOfMonth()->toDateString())->startOfDay();
            $end = Carbon::parse($this->dateTo ?: $start->toDateString())->endOfDay();

            return $end->lt($start)
                ? [$end->copy()->startOfDay(), $start->copy()->endOfDay()]
                : [$start, $end];
        }

        $date = Carbon::parse($this->referenceMonth.'-01');

        return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    /** @return array{Carbon, Carbon} */
    private function comparisonRange(Carbon $start, Carbon $end): array
    {
        $days = $start->diffInDays($end) + 1;
        $comparisonEnd = $start->copy()->subDay()->endOfDay();

        return [$comparisonEnd->copy()->subDays($days - 1)->startOfDay(), $comparisonEnd];
    }

    private function periodLabel(Carbon $start, Carbon $end): string
    {
        if ($this->period === 'year') {
            return $start->format('Y');
        }

        if ($this->period === 'custom') {
            return $start->format('d/m/Y').' – '.$end->format('d/m/Y');
        }

        return $start->translatedFormat('F \d\e Y');
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if ($previous === 0.0) {
            return $current === 0.0 ? 0.0 : null;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function chartMonths(Carbon $start, Carbon $end, $transactions): Collection
    {
        if ($this->period === 'month') {
            $itemsByDay = (clone $transactions)
                ->whereBetween('due_date', [$start, $end])
                ->get()
                ->groupBy(fn ($transaction): string => $transaction->due_date->toDateString());
            $days = collect();

            for ($day = $start->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
                $items = $itemsByDay->get($day->toDateString(), collect());
                $days->push([
                    'label' => $day->format('d/m'),
                    'income' => (float) $items->where('type', 'income')->sum('amount'),
                    'expense' => (float) $items->where('type', 'expense')->sum('amount'),
                ]);
            }

            return $days;
        }

        $chartStart = $start->copy()->startOfMonth();
        $chartEnd = $end->copy()->endOfMonth();
        $months = collect();

        for ($month = $chartStart->copy()->startOfMonth(); $month->lte($chartEnd); $month->addMonthNoOverflow()) {
            $monthEnd = $month->copy()->endOfMonth();
            $items = (clone $transactions)->whereBetween('due_date', [$month, $monthEnd]);
            $months->push([
                'label' => $month->translatedFormat('M/y'),
                'income' => (float) (clone $items)->where('type', 'income')->sum('amount'),
                'expense' => (float) (clone $items)->where('type', 'expense')->sum('amount'),
            ]);
        }

        return $months;
    }
}
