<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\HouseholdFinance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/login', Login::class)->middleware('guest')->name('login');
Route::get('/register', Register::class)->middleware('guest')->name('register');
Route::view('/termos-de-uso', 'terms')->name('terms');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', HouseholdFinance::class)->defaults('section', 'home')->name('dashboard');
    Route::get('/lancamentos', HouseholdFinance::class)->defaults('section', 'transactions')->name('transactions');
    Route::get('/recorrentes', HouseholdFinance::class)->defaults('section', 'recurring')->name('recurring');
    Route::get('/parcelamentos', HouseholdFinance::class)->defaults('section', 'installments')->name('installments');
    Route::get('/contas', HouseholdFinance::class)->defaults('section', 'accounts')->name('accounts');
});
