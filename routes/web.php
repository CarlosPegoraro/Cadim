<?php

use App\Http\Controllers\FinancialNotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\TransactionExportController;
use App\Livewire\AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\BudgetsAndGoalsPage;
use App\Livewire\CategoriesPage;
use App\Livewire\ChangelogPage;
use App\Livewire\Dashboard;
use App\Livewire\FinancialAccountsPage;
use App\Livewire\ProfileSettings;
use App\Livewire\SupportPage;
use App\Livewire\TransactionsPage;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');
Route::get('/login', Login::class)->middleware('guest')->name('login');
Route::get('/register', Register::class)->middleware('guest')->name('register');
Route::view('/termos-de-uso', 'terms')->name('terms');
Route::post('/logout', function () {
    if (Auth::check()) {
        app(AuditService::class)->record(Auth::user(), 'auth.logout');
    }
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/transactions', TransactionsPage::class)->name('transactions');
    Route::get('/transactions/export', TransactionExportController::class)->name('transactions.export');
    Route::get('/accounts-and-cards', FinancialAccountsPage::class)->name('accounts');
    Route::get('/categories', CategoriesPage::class)->name('categories');
    Route::get('/budgets-and-goals', BudgetsAndGoalsPage::class)->name('budgets');
    Route::get('/profile', ProfileSettings::class)->name('profile');
    Route::get('/suporte', SupportPage::class)->name('support');
    Route::get('/changelog', ChangelogPage::class)->name('changelog');
    Route::post('/onboarding/event', [OnboardingController::class, 'event'])->name('onboarding.event');
    Route::post('/suporte/feedback', [OnboardingController::class, 'feedback'])->name('support.feedback');
    Route::post('/notifications/read-all', [FinancialNotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [FinancialNotificationController::class, 'read'])->name('notifications.read');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
});
