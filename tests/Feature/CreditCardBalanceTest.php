<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CreditCardBalanceService;
use App\Services\RecurrenceService;
use Carbon\Carbon;

test('card balances calculate committed limit and current and next invoices', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-07 10:00:00'));

    try {
        $user = User::factory()->create();
        $card = $user->creditCards()->create([
            'name' => 'Cartão principal', 'closing_day' => 25, 'due_day' => 5, 'limit' => 1000,
        ]);

        $card->transactions()->createMany([
            [
                'user_id' => $user->id, 'type' => 'expense', 'amount' => 250,
                'description' => 'Compra pendente', 'purchase_date' => '2026-08-01',
                'due_date' => '2026-08-05', 'competence_month' => '2026-08-01', 'status' => 'pending',
            ],
            [
                'user_id' => $user->id, 'type' => 'expense', 'amount' => 400,
                'description' => 'Compra confirmada', 'purchase_date' => '2026-07-20',
                'due_date' => '2026-08-05', 'competence_month' => '2026-07-01', 'status' => 'settled',
                'settled_at' => '2026-08-05',
            ],
            [
                'user_id' => $user->id, 'type' => 'expense', 'amount' => 100,
                'description' => 'Próxima fatura', 'purchase_date' => '2026-08-26',
                'due_date' => '2026-09-05', 'competence_month' => '2026-08-01', 'status' => 'pending',
            ],
            [
                'user_id' => $user->id, 'type' => 'expense', 'amount' => 999,
                'description' => 'Cancelada', 'purchase_date' => '2026-08-02',
                'due_date' => '2026-08-05', 'competence_month' => '2026-08-01', 'status' => 'canceled',
            ],
        ]);

        $summary = app(CreditCardBalanceService::class)->summarize($card);

        expect($summary['used_limit'])->toBe(350.0)
            ->and($summary['available_limit'])->toBe(650.0)
            ->and($summary['current_invoice'])->toBe(650.0)
            ->and($summary['next_invoice'])->toBe(100.0);
    } finally {
        Carbon::setTestNow();
    }
});

test('recurring card transactions keep purchase date separate from invoice due date', function () {
    $user = User::factory()->create();
    $series = $user->transactionSeries()->create([
        'type' => 'expense', 'amount' => 120, 'description' => 'Compra parcelada',
        'credit_card_id' => $user->creditCards()->create([
            'name' => 'Cartão', 'closing_day' => 25, 'due_day' => 5, 'limit' => 1000,
        ])->id,
        'recurrence' => 'installment', 'starts_on' => '2026-09-05',
        'purchase_date' => '2026-08-26', 'ends_on' => '2026-10-05', 'installments' => 2,
    ]);

    app(RecurrenceService::class)->materialize($series, Carbon::parse('2026-12-31'));
    $occurrences = $series->occurrences()->orderBy('due_date')->get();

    expect($occurrences->pluck('due_date')->map->toDateString()->all())->toBe(['2026-09-05', '2026-10-05'])
        ->and($occurrences->pluck('purchase_date')->map->toDateString()->all())->toBe(['2026-08-26', '2026-09-26'])
        ->and($occurrences->first()->competence_month->toDateString())->toBe('2026-08-01');
});

test('a card invoice can be marked as paid and unpaid', function () {
    $user = User::factory()->create();
    $card = $user->creditCards()->create([
        'name' => 'Cartão', 'closing_day' => 25, 'due_day' => 5, 'limit' => 1000,
    ]);
    $service = app(CreditCardBalanceService::class);
    $month = Carbon::parse('2026-08-01');

    $service->payInvoice($card, $month, 250, Carbon::parse('2026-08-05'));
    expect($service->summarize($card, $month)['current_invoice_paid'])->toBeTrue();

    $service->unpaidInvoice($card, $month);
    expect($service->summarize($card, $month)['current_invoice_paid'])->toBeFalse();
});
