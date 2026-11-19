<?php

namespace App\Services;

use App\Models\TransactionOccurrence;
use App\Models\TransactionSeries;
use Carbon\Carbon;

class RecurrenceService
{
    public function defaultUntil(?int $months = null): Carbon
    {
        return now()->addMonths($months ?? config('recurrence.materialization_months', 6))->endOfMonth();
    }

    public function materialize(TransactionSeries $series, ?Carbon $until = null): void
    {
        $until ??= $this->defaultUntil();
        $date = $series->starts_on->copy();
        $purchaseDate = ($series->purchase_date ?? $series->starts_on)->copy();
        $limit = $series->ends_on?->copy() ?? $until;
        if ($series->recurrence === 'one_time') {
            $limit = $date;
        } elseif ($limit->gt($until)) {
            $limit = $until;
        }
        for ($number = 1; $date->lte($limit); $date->addMonthNoOverflow(),$number++) {
            $occurrenceDate = $date->copy()->startOfDay();
            $occurrencePurchaseDate = $purchaseDate->copy()->addMonthsNoOverflow($number - 1)->startOfDay();
            TransactionOccurrence::firstOrCreate(['transaction_series_id' => $series->id, 'due_date' => $occurrenceDate], ['user_id' => $series->user_id, 'category_id' => $series->category_id, 'financial_account_id' => $series->financial_account_id, 'credit_card_id' => $series->credit_card_id, 'type' => $series->type, 'amount' => $series->amount, 'description' => $series->description, 'merchant' => $series->merchant, 'notes' => $series->notes, 'due_date' => $occurrenceDate, 'purchase_date' => $occurrencePurchaseDate, 'competence_month' => $occurrencePurchaseDate->copy()->startOfMonth(), 'status' => 'settled', 'settled_at' => $occurrenceDate, 'installment_number' => $series->recurrence === 'installment' ? $number : null]);
            if ($series->recurrence === 'one_time') {
                break;
            }
        }
    }
}
