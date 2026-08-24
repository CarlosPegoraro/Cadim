<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\TransactionOccurrence;
use App\Models\TransactionSeries;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(private readonly RecurrenceService $recurrence) {}

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): TransactionSeries
    {
        return DB::transaction(function () use ($user, $data) {
            $data['purchase_date'] = Carbon::parse($data['purchase_date'] ?? $data['due_date'])->toDateString();
            $this->validateDomainReferences($user, $data);
            $data = $this->prepareCardDueDate($user, $data);
            $data['installments'] = $data['recurrence'] === 'installment'
                ? (int) $data['installments']
                : null;
            if ($data['recurrence'] === 'installment') {
                $data['amount'] = round((float) $data['amount'] / $data['installments'], 2);
            }
            $end = $data['recurrence'] === 'installment'
                ? Carbon::parse($data['due_date'])->addMonths(((int) $data['installments']) - 1)
                : null;

            $series = $user->transactionSeries()->create($data + [
                'starts_on' => $data['due_date'],
                'ends_on' => $end,
                'installments' => $data['recurrence'] === 'installment' ? $data['installments'] : null,
            ]);

            $this->recurrence->materialize($series);

            return $series;
        });
    }

    public function createOpeningBalance(User $user, FinancialAccount $account, float $balance): TransactionOccurrence
    {
        $amount = abs($balance);
        $date = today();

        $series = $user->transactionSeries()->create([
            'type' => $balance < 0 ? 'expense' : 'income',
            'amount' => $amount,
            'description' => 'Saldo inicial da conta',
            'financial_account_id' => $account->id,
            'recurrence' => 'one_time',
            'starts_on' => $date,
            'purchase_date' => $date,
            'is_active' => true,
        ]);

        return $series->occurrences()->create([
            'user_id' => $user->id,
            'financial_account_id' => $account->id,
            'type' => $balance < 0 ? 'expense' : 'income',
            'amount' => $amount,
            'description' => 'Saldo inicial da conta',
            'due_date' => $date,
            'purchase_date' => $date,
            'competence_month' => $date->copy()->startOfMonth(),
            'status' => 'settled',
            'settled_at' => $date,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function updateOccurrence(TransactionOccurrence $occurrence, array $data): void
    {
        $this->assertAuthenticatedOwner($occurrence);
        $this->validateDomainReferences($occurrence->user, $data);
        $data['purchase_date'] = Carbon::parse($data['purchase_date'] ?? $data['due_date'])->toDateString();
        $data = $this->prepareCardDueDate($occurrence->user, $data);
        $data['settled_at'] = $data['status'] === 'settled'
            ? ($data['settled_at'] ?? today())
            : null;

        DB::transaction(function () use ($occurrence, $data) {
            $occurrence->update($this->occurrenceAttributes($data) + [
                // An individual edit must not be overwritten by its recurring series.
                'transaction_series_id' => null,
                'installment_number' => null,
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateSeries(TransactionOccurrence $occurrence, array $data): void
    {
        $series = $occurrence->series;

        if (! $series) {
            $this->updateOccurrence($occurrence, $data);

            return;
        }

        $this->assertAuthenticatedOwner($occurrence);
        $this->validateDomainReferences($occurrence->user, $data);
        $attributes = $this->occurrenceAttributes($data);

        DB::transaction(function () use ($series, $occurrence, $attributes) {
            $series->update($attributes);

            $series->occurrences()
                ->where('status', 'pending')
                ->whereDate('due_date', '>=', $occurrence->due_date)
                ->update($attributes);
        });
    }

    public function duplicate(TransactionOccurrence $occurrence): TransactionSeries
    {
        $this->assertAuthenticatedOwner($occurrence);

        return DB::transaction(function () use ($occurrence) {
            $series = $occurrence->user->transactionSeries()->create([
                'type' => $occurrence->type,
                'amount' => $occurrence->amount,
                'description' => $occurrence->description,
                'merchant' => $occurrence->merchant,
                'notes' => $occurrence->notes,
                'category_id' => $occurrence->category_id,
                'financial_account_id' => $occurrence->financial_account_id,
                'credit_card_id' => $occurrence->credit_card_id,
                'recurrence' => 'one_time',
                'starts_on' => $occurrence->due_date,
                'purchase_date' => $occurrence->purchase_date ?? $occurrence->due_date,
                'ends_on' => null,
                'installments' => null,
                'is_active' => true,
            ]);

            $this->recurrence->materialize($series, $occurrence->due_date);

            return $series;
        });
    }

    public function toggleSettled(TransactionOccurrence $occurrence, ?Carbon $date = null): void
    {
        $this->assertAuthenticatedOwner($occurrence);

        if ($occurrence->status === 'settled') {
            $occurrence->update(['status' => 'pending', 'settled_at' => null]);

            return;
        }

        $occurrence->update(['status' => 'settled', 'settled_at' => $date ?? today()]);
    }

    public function cancel(TransactionOccurrence $occurrence): void
    {
        $this->assertAuthenticatedOwner($occurrence);
        $occurrence->update(['status' => 'canceled', 'settled_at' => null]);
    }

    public function cancelFuture(TransactionOccurrence $occurrence): void
    {
        $this->assertAuthenticatedOwner($occurrence);

        DB::transaction(function () use ($occurrence) {
            if ($occurrence->series) {
                $occurrence->series->update([
                    'is_active' => false,
                    'ends_on' => $occurrence->due_date->copy()->subDay(),
                ]);

                $occurrence->series->occurrences()
                    ->where('status', 'pending')
                    ->whereDate('due_date', '>=', $occurrence->due_date)
                    ->update(['status' => 'canceled', 'settled_at' => null]);
            } else {
                $this->cancel($occurrence);
            }
        });
    }

    public function deleteOccurrence(TransactionOccurrence $occurrence): void
    {
        $this->assertAuthenticatedOwner($occurrence, 'delete');

        $series = $occurrence->series;
        $occurrence->delete();

        if ($series?->recurrence === 'one_time') {
            $series->delete();
        }
    }

    /** @param array<string, mixed> $data */
    private function occurrenceAttributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'type', 'amount', 'description', 'merchant', 'notes', 'category_id',
            'financial_account_id', 'credit_card_id', 'due_date', 'purchase_date', 'status', 'settled_at',
        ]));
    }

    /** @param array<string, mixed> $data */
    private function prepareCardDueDate(User $user, array $data): array
    {
        if (! filled($data['credit_card_id'] ?? null)) {
            return $data;
        }

        $card = $user->creditCards()->findOrFail($data['credit_card_id']);
        $purchase = Carbon::parse($data['purchase_date'] ?? $data['due_date']);
        $cycle = $purchase->copy()->startOfMonth();

        if ($purchase->day > $card->closing_day) {
            $cycle->addMonth();
        }

        $due = $cycle->copy()->day(min($card->due_day, $cycle->daysInMonth));
        if ($due->lt($purchase)) {
            $due->addMonth();
        }

        $data['due_date'] = $due->toDateString();

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function validateDomainReferences(User $user, array $data): void
    {
        $type = $data['type'] ?? null;
        $categoryId = filled($data['category_id'] ?? null) ? (int) $data['category_id'] : null;
        $accountId = filled($data['financial_account_id'] ?? null) ? (int) $data['financial_account_id'] : null;
        $cardId = filled($data['credit_card_id'] ?? null) ? (int) $data['credit_card_id'] : null;

        if ($categoryId) {
            $category = $user->categories()->find($categoryId);
            abort_unless($category, 403);
            if ($category->is_archived || $category->type !== $type) {
                throw ValidationException::withMessages(['category_id' => 'A categoria precisa corresponder ao tipo do lançamento e estar ativa.']);
            }
        }

        if ($accountId) {
            abort_unless($user->accounts()->whereKey($accountId)->exists(), 403);
        }

        if ($cardId) {
            $card = $user->creditCards()->find($cardId);
            abort_unless($card, 403);
            if ($card->is_archived) {
                throw ValidationException::withMessages(['credit_card_id' => 'Não é possível usar um cartão arquivado.']);
            }
            if ($type !== 'expense') {
                throw ValidationException::withMessages(['credit_card_id' => 'Cartões só podem ser usados em despesas.']);
            }
        }
    }

    private function assertAuthenticatedOwner(TransactionOccurrence $occurrence, string $ability = 'update'): void
    {
        if (auth()->check()) {
            Gate::forUser(auth()->user())->authorize($ability, $occurrence);
        }
    }
}
