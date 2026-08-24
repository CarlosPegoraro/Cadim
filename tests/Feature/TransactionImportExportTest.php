<?php

use App\Models\User;
use App\Services\RecurrenceService;
use App\Services\TransactionImportService;
use Illuminate\Http\UploadedFile;

test('a CSV is previewed with automatic column mapping', function () {
    $file = UploadedFile::fake()->createWithContent('transactions.csv', "descricao;valor;data;tipo\nMercado;125,50;10/08/2026;saida\n");

    $preview = app(TransactionImportService::class)->preview($file);

    expect($preview['headers'])->toBe(['descricao', 'valor', 'data', 'tipo'])
        ->and($preview['mapping']['description'])->toBe('descricao')
        ->and($preview['mapping']['amount'])->toBe('valor')
        ->and($preview['mapping']['due_date'])->toBe('data')
        ->and($preview['rows'])->toHaveCount(1);
});

test('mapped CSV rows create isolated one-time transactions', function () {
    $user = User::factory()->create();
    $user->categories()->create(['name' => 'Alimentação', 'type' => 'expense']);
    $user->accounts()->create(['name' => 'Conta principal']);
    $file = UploadedFile::fake()->createWithContent('transactions.csv', "descricao,valor,data,tipo,categoria,conta,status\nMercado,125.50,2026-08-10,expense,Alimentação,Conta principal,settled\n");
    $service = app(TransactionImportService::class);
    $preview = $service->preview($file);

    expect($service->import($user, $preview['headers'], $preview['rows'], $preview['mapping']))->toBe(1);
    $this->assertDatabaseHas('transaction_occurrences', [
        'user_id' => $user->id,
        'description' => 'Mercado',
        'status' => 'settled',
        'amount' => '125.50',
    ]);
});

test('reimporting the same CSV skips duplicate transactions', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->createWithContent('transactions.csv', "descricao,valor,data,tipo\nMercado,125.50,2026-08-10,expense\n");
    $service = app(TransactionImportService::class);
    $preview = $service->preview($file);

    expect($service->import($user, $preview['headers'], $preview['rows'], $preview['mapping']))->toBe(1)
        ->and($service->import($user, $preview['headers'], $preview['rows'], $preview['mapping']))->toBe(0)
        ->and($service->duplicatesSkipped)->toBe(1)
        ->and($user->transactions()->count())->toBe(1);
});

test('transaction export respects the authenticated user and filters', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $user->transactionSeries()->create(['type' => 'expense', 'amount' => 80, 'description' => 'Minha compra', 'recurrence' => 'one_time', 'starts_on' => '2026-08-10']);
    $otherUser->transactionSeries()->create(['type' => 'expense', 'amount' => 90, 'description' => 'Compra privada', 'recurrence' => 'one_time', 'starts_on' => '2026-08-10']);
    app(RecurrenceService::class)->materialize($user->transactionSeries()->first());
    app(RecurrenceService::class)->materialize($otherUser->transactionSeries()->first());

    $response = $this->actingAs($user)->get('/transactions/export?status=pending');

    $response->assertOk();
    expect($response->streamedContent())->toContain('Minha compra')->not->toContain('Compra privada');
});
