<?php

use App\Livewire\HouseholdFinance;
use App\Models\User;
use Livewire\Livewire;

it('creates a pending expense owned by the signed-in user', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(HouseholdFinance::class, ['section' => 'transactions'])
        ->set('description', 'Manutenção residencial')
        ->set('amount', '350,00')
        ->set('type', 'expense')
        ->set('date', '2026-11-20')
        ->call('create')
        ->assertHasNoErrors();

    expect($user->transactions()->first())
        ->description->toBe('Manutenção residencial')
        ->amount->toBe('350.00')
        ->status->toBe('pending');
});

it('confirms and reverses a payment with its effective date', function () {
    $user = User::factory()->create();
    $entry = $user->transactions()->create([
        'type' => 'expense', 'amount' => '100.00', 'description' => 'Internet',
        'due_date' => today(), 'competence_month' => today()->startOfMonth(), 'status' => 'pending',
    ]);

    Livewire::actingAs($user)->test(HouseholdFinance::class)->call('toggle', $entry->id);
    expect($entry->fresh()->status)->toBe('settled')->and($entry->fresh()->settled_at->toDateString())->toBe(today()->toDateString());

    Livewire::actingAs($user)->test(HouseholdFinance::class)->call('toggle', $entry->id);
    expect($entry->fresh()->status)->toBe('pending')->and($entry->fresh()->settled_at)->toBeNull();
});

it('splits installment cents deterministically and keeps records private', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Livewire::actingAs($owner)->test(HouseholdFinance::class, ['section' => 'installments'])
        ->set('description', 'Compra da casa')->set('type', 'expense')->set('date', '2026-12-31')
        ->set('total', '100,00')->set('installments', '3')->call('create')->assertHasNoErrors();

    $rows = $owner->transactions()->orderBy('due_date')->get();
    expect($rows)->toHaveCount(3)
        ->and($rows->sum(fn ($row) => (int) round((float) $row->amount * 100)))->toBe(10000)
        ->and($rows->pluck('installment_number')->all())->toBe([1, 2, 3])
        ->and($other->transactions()->count())->toBe(0);
});

it('creates monthly occurrences with end of month clamping', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(HouseholdFinance::class, ['section' => 'recurring'])
        ->set('description', 'Conta de água')->set('amount', '80,00')->set('type', 'expense')
        ->set('date', '2026-01-01')->set('day', '31')->call('create')->assertHasNoErrors();

    expect($user->transactions()->orderBy('due_date')->get()->map(fn ($row) => $row->due_date->toDateString())->all())->toContain('2026-02-28');
});

it('updates future recurring amounts without changing paid history and stops projections when disabled', function () {
    $user = User::factory()->create();
    $today = today();
    $day = $today->day;
    Livewire::actingAs($user)->test(HouseholdFinance::class, ['section' => 'recurring'])
        ->set('description', 'Energia')->set('amount', '500,00')->set('type', 'expense')
        ->set('date', $today->copy()->startOfMonth()->toDateString())->set('day', (string) $day)->call('create')->assertHasNoErrors();
    $series = $user->transactionSeries()->firstOrFail();
    $current = $user->transactions()->whereDate('due_date', $today)->firstOrFail();
    Livewire::actingAs($user)->test(HouseholdFinance::class)->call('toggle', $current->id);

    Livewire::actingAs($user)->test(HouseholdFinance::class)
        ->call('editFutureSeries', $series->id)->set('amount', '463,80')->call('saveFutureSeries')->assertHasNoErrors();
    expect($current->fresh()->amount)->toBe('500.00')
        ->and($series->fresh()->occurrences()->where('status', 'pending')->whereDate('due_date', '>', $today)->first()->amount)->toBe('463.80');

    Livewire::actingAs($user)->test(HouseholdFinance::class)->call('endSeries', $series->id);
    expect($series->fresh()->is_active)->toBeFalse()
        ->and($series->fresh()->occurrences()->where('status', 'canceled')->count())->toBeGreaterThan(0)
        ->and($current->fresh()->status)->toBe('settled');
});

it('totals credit card items as an invoice without creating another expense', function () {
    $user = User::factory()->create();
    $card = $user->accounts()->create(['name' => 'C6 Bank', 'type' => 'credit']);
    $user->transactions()->create(['financial_account_id' => $card->id, 'type' => 'expense', 'amount' => '120.50', 'description' => 'Parcela', 'due_date' => today(), 'competence_month' => today()->startOfMonth(), 'status' => 'pending']);
    $user->transactions()->create(['financial_account_id' => $card->id, 'type' => 'expense', 'amount' => '30.25', 'description' => 'Mercado', 'due_date' => today(), 'competence_month' => today()->startOfMonth(), 'status' => 'pending']);

    $component = Livewire::actingAs($user)->test(HouseholdFinance::class, ['section' => 'accounts']);
    expect($component->viewData('creditInvoices')[$card->id])->toBe(15075)
        ->and($user->transactions()->count())->toBe(2);
});
