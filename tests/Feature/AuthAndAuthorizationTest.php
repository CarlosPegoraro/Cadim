<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\TransactionsPage;
use App\Models\TransactionOccurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('guests are redirected from every authenticated page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'dashboard', 'transactions', 'accounts', 'categories', 'budgets', 'profile', 'support', 'changelog',
]);

test('registration requires accepted terms and matching credentials', function () {
    Livewire::test(Register::class)
        ->set('name', 'Pessoa Teste')
        ->set('email', 'registration@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'different')
        ->call('register')
        ->assertHasErrors(['password', 'terms_accepted']);

    expect(User::where('email', 'registration@example.com')->exists())->toBeFalse();
});

test('registration creates a regular user, seeds categories and audits the event', function () {
    Livewire::test(Register::class)
        ->set('name', 'Pessoa Teste')
        ->set('email', 'registration@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('terms_accepted', true)
        ->call('register')
        ->assertRedirect(route('dashboard'));

    $user = User::where('email', 'registration@example.com')->firstOrFail();

    expect($user->role)->toBe('user')
        ->and($user->categories)->toHaveCount(10)
        ->and(Hash::check('password', $user->password))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'auth.register']);
});

test('login rejects invalid credentials', function () {
    $user = User::factory()->create(['email' => 'login@example.com', 'password' => 'password']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

test('a user cannot read another user transaction through the Livewire page', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $series = $other->transactionSeries()->create([
        'type' => 'expense', 'amount' => 75, 'description' => 'Segredo financeiro',
        'recurrence' => 'one_time', 'starts_on' => '2026-08-10',
    ]);
    $occurrence = TransactionOccurrence::create([
        'user_id' => $other->id, 'transaction_series_id' => $series->id,
        'type' => 'expense', 'amount' => 75, 'description' => 'Segredo financeiro',
        'due_date' => '2026-08-10', 'competence_month' => '2026-08-01', 'status' => 'pending',
    ]);

    expect(fn () => Livewire::actingAs($user)->test(TransactionsPage::class)
        ->call('openEdit', $occurrence->id))
        ->toThrow(ModelNotFoundException::class);
});
