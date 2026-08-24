<?php

namespace App\Services;

use App\Models\CreditCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CreditCardBalanceService
{
    /** @return array<string, mixed> */
    public function summarize(CreditCard $card, ?Carbon $reference = null): array
    {
        $reference ??= now();
        $transactions = $card->transactions()
            ->where('type', 'expense')
            ->where('status', '!=', 'canceled');
        $pending = (clone $transactions)->where('status', 'pending');
        $usedLimit = (float) $pending->sum('amount');
        $limit = $card->limit !== null ? (float) $card->limit : null;

        $currentInvoice = (clone $transactions)
            ->whereBetween('due_date', [$reference->copy()->startOfMonth(), $reference->copy()->endOfMonth()])
            ->sum('amount');
        $nextMonth = $reference->copy()->addMonthNoOverflow();
        $nextInvoice = (clone $transactions)
            ->whereBetween('due_date', [$nextMonth->copy()->startOfMonth(), $nextMonth->copy()->endOfMonth()])
            ->sum('amount');
        $currentPayment = $card->invoicePayments()->whereDate('invoice_month', $reference->copy()->startOfMonth())->first();
        $nextPayment = $card->invoicePayments()->whereDate('invoice_month', $nextMonth->copy()->startOfMonth())->first();

        return [
            'limit' => $limit,
            'used_limit' => $usedLimit,
            'available_limit' => $limit !== null ? $limit - $usedLimit : null,
            'current_invoice' => (float) $currentInvoice,
            'next_invoice' => (float) $nextInvoice,
            'current_invoice_due' => $this->dueDate($card, $reference),
            'next_invoice_due' => $this->dueDate($card, $nextMonth),
            'current_invoice_paid' => $currentPayment !== null,
            'current_invoice_paid_at' => $currentPayment?->paid_at?->toDateString(),
            'next_invoice_paid' => $nextPayment !== null,
        ];
    }

    public function payInvoice(CreditCard $card, Carbon $invoiceMonth, float $amount, Carbon $paidAt): void
    {
        $card->invoicePayments()->updateOrCreate(
            ['user_id' => $card->user_id, 'invoice_month' => $invoiceMonth->copy()->startOfMonth()->toDateString()],
            ['amount' => $amount, 'paid_at' => $paidAt->toDateString()]
        );
    }

    public function unpaidInvoice(CreditCard $card, Carbon $invoiceMonth): void
    {
        $card->invoicePayments()->whereDate('invoice_month', $invoiceMonth->copy()->startOfMonth())->delete();
    }

    /**
     * @return array{cards: Collection<int, array<string, float|int|null|string>>, consolidated: array<string, float|int|null>}
     */
    public function summarizeForUser(User $user, ?Carbon $reference = null): array
    {
        $cards = $user->creditCards()
            ->where('is_archived', false)
            ->orderBy('name')
            ->get();
        $summaries = $cards->mapWithKeys(
            fn (CreditCard $card): array => [$card->id => $this->summarize($card, $reference)]
        );

        return [
            'cards' => $summaries,
            'consolidated' => [
                'limit' => (float) $summaries->sum('limit'),
                'used_limit' => (float) $summaries->sum('used_limit'),
                'available_limit' => (float) $summaries->sum('available_limit'),
                'current_invoice' => (float) $summaries->sum('current_invoice'),
                'next_invoice' => (float) $summaries->sum('next_invoice'),
            ],
        ];
    }

    private function dueDate(CreditCard $card, Carbon $month): string
    {
        return $month->copy()->day(min($card->due_day, $month->daysInMonth))->toDateString();
    }
}
