<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AccountBalanceService
{
    /**
     * @return array<string, float>
     */
    public function summarize(FinancialAccount $account, ?Carbon $asOf = null): array
    {
        $transactions = $account->transactions()->where('status', '!=', 'canceled');
        if ($asOf) {
            $transactions->whereDate('due_date', '<=', $asOf);
        }
        $settled = (clone $transactions)->where('status', 'settled');
        $pending = (clone $transactions)->where('status', 'pending');

        $settledIncome = (float) (clone $settled)->where('type', 'income')->sum('amount');
        $settledExpense = (float) (clone $settled)->where('type', 'expense')->sum('amount');
        $pendingIncome = (float) (clone $pending)->where('type', 'income')->sum('amount');
        $pendingExpense = (float) (clone $pending)->where('type', 'expense')->sum('amount');
        $transfersIn = $account->incomingTransfers()->where('status', '!=', 'canceled');
        $transfersOut = $account->outgoingTransfers()->where('status', '!=', 'canceled');
        if ($asOf) {
            $transfersIn->whereDate('transfer_date', '<=', $asOf);
            $transfersOut->whereDate('transfer_date', '<=', $asOf);
        }
        $settledTransferIn = (float) (clone $transfersIn)->where('status', 'settled')->sum('amount');
        $settledTransferOut = (float) (clone $transfersOut)->where('status', 'settled')->sum('amount');
        $pendingTransferIn = (float) (clone $transfersIn)->where('status', 'pending')->sum('amount');
        $pendingTransferOut = (float) (clone $transfersOut)->where('status', 'pending')->sum('amount');
        $initialBalance = (float) $account->initial_balance;

        return [
            'initial_balance' => $initialBalance,
            'settled_income' => $settledIncome,
            'settled_expense' => $settledExpense,
            'pending_income' => $pendingIncome,
            'pending_expense' => $pendingExpense,
            'settled_transfer_in' => $settledTransferIn,
            'settled_transfer_out' => $settledTransferOut,
            'pending_transfer_in' => $pendingTransferIn,
            'pending_transfer_out' => $pendingTransferOut,
            'realized_balance' => $initialBalance + $settledIncome - $settledExpense + $settledTransferIn - $settledTransferOut,
            'projected_balance' => $initialBalance + $settledIncome - $settledExpense + $settledTransferIn - $settledTransferOut + $pendingIncome - $pendingExpense + $pendingTransferIn - $pendingTransferOut,
        ];
    }

    /**
     * @return array{accounts: Collection<int, array<string, float>>, consolidated: array<string, float>, net_worth: array<string, float>}
     */
    public function summarizeForUser(User $user, ?Carbon $asOf = null, ?array $consolidatedTypes = null): array
    {
        $accounts = $user->accounts()
            ->where('is_archived', false)
            ->orderBy('name')
            ->get();

        $summaries = $accounts->mapWithKeys(
            fn (FinancialAccount $account): array => [$account->id => $this->summarize($account, $asOf)]
        );

        $operationalSummaries = $accounts
            ->filter(fn (FinancialAccount $account): bool => $consolidatedTypes === null
                ? ! $account->isInvestment()
                : in_array($account->type, $consolidatedTypes, true))
            ->mapWithKeys(fn (FinancialAccount $account): array => [$account->id => $summaries[$account->id]]);
        $sum = static fn (Collection $items, string $key): float => (float) $items->sum($key);
        $keys = ['initial_balance', 'settled_income', 'settled_expense', 'pending_income', 'pending_expense', 'settled_transfer_in', 'settled_transfer_out', 'pending_transfer_in', 'pending_transfer_out', 'realized_balance', 'projected_balance'];
        $consolidated = collect($keys)->mapWithKeys(fn (string $key): array => [$key => $sum($operationalSummaries, $key)])->all();
        $netWorth = collect($keys)->mapWithKeys(fn (string $key): array => [$key => $sum($summaries, $key)])->all();

        return [
            'accounts' => $summaries,
            'consolidated' => $consolidated,
            'net_worth' => $netWorth,
        ];
    }

    public function currentBalanceForUser(User $user, ?Carbon $asOf = null): float
    {
        $asOf ??= now();

        return (float) $user->accounts()
            ->where('is_archived', false)
            ->whereNotIn('type', ['investments', 'savings'])
            ->get()
            ->sum(fn (FinancialAccount $account): float => $this->summarize($account, $asOf)['realized_balance']);
    }
}
