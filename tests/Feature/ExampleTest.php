<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Livewire\FinancialAccountsPage;
use App\Livewire\ProfileSettings;
use App\Models\User;
use Livewire\Livewire;

test('visitors can open the public landing page', function () {
    $this->get('/')->assertOk()
        ->assertSee('Seu dinheiro no lugar')
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});

test('application URLs use English slugs', function () {
    expect(route('login', absolute: false))->toBe('/login')
        ->and(route('register', absolute: false))->toBe('/register')
        ->and(route('logout', absolute: false))->toBe('/logout')
        ->and(route('transactions', absolute: false))->toBe('/transactions')
        ->and(route('accounts', absolute: false))->toBe('/accounts-and-cards')
        ->and(route('categories', absolute: false))->toBe('/categories')
        ->and(route('budgets', absolute: false))->toBe('/budgets-and-goals');
});

test('an authenticated user can open dashboard', function () {
    $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
});

test('registration creates default categories for the user', function () {
    Livewire::test(Register::class)
        ->set('name', 'New User')
        ->set('email', 'new@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('terms_accepted', true)
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'new@example.com')->firstOrFail();

    $this->assertDatabaseCount('categories', 10);
    expect($user->categories()->count())->toBe(10);
    expect($user->terms_accepted_at)->not->toBeNull()
        ->and($user->terms_version)->toBe('2026-08-14');
});

test('registration requires acceptance of the terms', function () {
    Livewire::test(Register::class)
        ->set('name', 'Without Terms')
        ->set('email', 'without-terms@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['terms_accepted' => 'accepted']);

    expect(User::where('email', 'without-terms@example.com')->exists())->toBeFalse();
});

test('registration rejects an admin flag', function () {
    Livewire::test(Register::class)
        ->set('name', 'Attempted Admin')
        ->set('email', 'attempted-admin@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('terms_accepted', true)
        ->set('admin', true)
        ->call('register')
        ->assertHasErrors(['admin']);

    expect(User::where('email', 'attempted-admin@example.com')->exists())->toBeFalse();
});

test('profile update rejects an admin flag', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProfileSettings::class)
        ->set('admin', true)
        ->call('saveProfile')
        ->assertHasErrors(['admin']);

    expect($user->fresh()->role)->toBe('user');
});

test('an authenticated user can save a credit card', function () {
    $user = User::factory()->create();
    $account = $user->accounts()->create(['name' => 'Conta do cartão', 'type' => 'checking', 'initial_balance' => 0]);

    Livewire::actingAs($user)
        ->test(FinancialAccountsPage::class)
        ->set('card.name', 'Cartão principal')
        ->set('card.brand', 'Visa')
        ->set('card.financial_account_id', $account->id)
        ->set('card.closing_day', 10)
        ->set('card.due_day', '05')
        ->set('card.limit', 2500)
        ->call('saveCard')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('credit_cards', [
        'user_id' => $user->id,
        'name' => 'Cartão principal',
        'closing_day' => 10,
        'due_day' => 5,
        'financial_account_id' => $account->id,
    ]);
});

test('an authenticated user can save a credit card without a limit', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FinancialAccountsPage::class)
        ->set('card.name', 'Cartão sem limite')
        ->set('card.brand', 'Hipercard')
        ->set('card.closing_day', 5)
        ->set('card.due_day', 27)
        ->set('card.limit', '')
        ->call('saveCard')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('credit_cards', [
        'user_id' => $user->id,
        'name' => 'Cartão sem limite',
        'limit' => null,
    ]);
});
