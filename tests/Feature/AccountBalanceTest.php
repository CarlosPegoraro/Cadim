<?php

namespace Tests\Feature;

use App\Livewire\FinancialAccountsPage;
use App\Models\User;
use App\Services\AccountBalanceService;
use App\Services\TransferService;
use Livewire\Livewire;

test('an account exposes realized and projected balances', function () {
    $user = User::factory()->create();
    $account = $user->accounts()->create([
        'name' => 'Conta principal',
        'initial_balance' => 1000,
    ]);

    $account->transactions()->createMany([
        [
            'user_id' => $user->id, 'type' => 'income', 'amount' => 500,
            'description' => 'Salário', 'due_date' => '2026-08-01',
            'competence_month' => '2026-08-01', 'status' => 'settled',
            'settled_at' => '2026-08-01',
        ],
        [
            'user_id' => $user->id, 'type' => 'expense', 'amount' => 200,
            'description' => 'Aluguel', 'due_date' => '2026-08-05',
            'competence_month' => '2026-08-01', 'status' => 'settled',
            'settled_at' => '2026-08-05',
        ],
        [
            'user_id' => $user->id, 'type' => 'income', 'amount' => 100,
            'description' => 'Freela', 'due_date' => '2026-08-20',
            'competence_month' => '2026-08-01', 'status' => 'pending',
        ],
        [
            'user_id' => $user->id, 'type' => 'expense', 'amount' => 50,
            'description' => 'Mercado', 'due_date' => '2026-08-21',
            'competence_month' => '2026-08-01', 'status' => 'pending',
        ],
        [
            'user_id' => $user->id, 'type' => 'expense', 'amount' => 999,
            'description' => 'Cancelado', 'due_date' => '2026-08-22',
            'competence_month' => '2026-08-01', 'status' => 'canceled',
        ],
    ]);

    $summary = app(AccountBalanceService::class)->summarize($account);

    expect($summary['realized_balance'])->toBe(1300.0)
        ->and($summary['projected_balance'])->toBe(1350.0)
        ->and($summary['pending_income'])->toBe(100.0)
        ->and($summary['pending_expense'])->toBe(50.0);
});

test('the accounts page displays consolidated balances for active accounts', function () {
    $user = User::factory()->create();
    $account = $user->accounts()->create(['name' => 'Conta principal', 'initial_balance' => 1000]);
    $account->transactions()->create([
        'user_id' => $user->id, 'type' => 'income', 'amount' => 300,
        'description' => 'Entrada', 'due_date' => '2026-08-01',
        'competence_month' => '2026-08-01', 'status' => 'settled',
        'settled_at' => '2026-08-01',
    ]);

    Livewire::actingAs($user)
        ->test(FinancialAccountsPage::class)
        ->assertSee('R$ 1.300,00')
        ->assertSee('Saldo atual consolidado');
});

test('creating an account records its opening balance in the transaction history', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FinancialAccountsPage::class)
        ->set('account.name', 'Conta nova')
        ->set('account.type', 'checking')
        ->set('account.initial_balance', '1250.50')
        ->call('saveAccount')
        ->assertHasNoErrors();

    $account = $user->accounts()->where('name', 'Conta nova')->firstOrFail();
    $transaction = $user->transactions()->where('description', 'Saldo inicial da conta')->firstOrFail();

    expect($account->initial_balance)->toBe('0.00')
        ->and($transaction->financial_account_id)->toBe($account->id)
        ->and($transaction->type)->toBe('income')
        ->and($transaction->amount)->toBe('1250.50')
        ->and($transaction->status)->toBe('settled')
        ->and($transaction->due_date->toDateString())->toBe(today()->toDateString())
        ->and(app(AccountBalanceService::class)->summarize($account)['realized_balance'])->toBe(1250.50);
});

test('investment accounts are excluded from available balance but included in net worth', function () {
    $user = User::factory()->create();
    $checking = $user->accounts()->create(['name' => 'Conta corrente', 'type' => 'checking', 'initial_balance' => 1000]);
    $investment = $user->accounts()->create(['name' => 'Investimentos', 'type' => 'investments', 'initial_balance' => 500]);
    app(TransferService::class)->create($user, [
        'from_account_id' => $checking->id,
        'to_account_id' => $investment->id,
        'amount' => 200,
        'transfer_date' => '2026-08-09',
        'status' => 'settled',
    ]);

    $summary = app(AccountBalanceService::class)->summarizeForUser($user);

    expect($summary['consolidated']['realized_balance'])->toBe(800.0)
        ->and($summary['net_worth']['realized_balance'])->toBe(1500.0);
});
