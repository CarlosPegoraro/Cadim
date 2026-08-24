<?php

use App\Models\User;
use App\Services\FinancialNotificationService;

test('financial notifications persist until the user reads them', function () {
    $user = User::factory()->create();
    $user->transactions()->create([
        'type' => 'expense', 'amount' => 100, 'description' => 'Conta vencida',
        'due_date' => now()->subDay()->toDateString(), 'purchase_date' => now()->subDay()->toDateString(),
        'competence_month' => now()->startOfMonth(), 'status' => 'pending',
    ]);

    $service = app(FinancialNotificationService::class);
    $first = $service->forUser($user);
    expect($first['count'])->toBe(1)->and($user->financialNotifications()->count())->toBe(1);

    $service->markAllRead($user);
    $second = $service->forUser($user->fresh());
    expect($second['count'])->toBe(0)->and($second['items'][0]['read'])->toBeTrue();
});
