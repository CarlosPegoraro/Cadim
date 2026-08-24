<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'avatar_icon', 'terms_accepted_at', 'terms_version', 'onboarding_completed_at', 'role', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'terms_accepted_at' => 'datetime', 'onboarding_completed_at' => 'datetime', 'last_login_at' => 'datetime'];
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function accounts()
    {
        return $this->hasMany(FinancialAccount::class);
    }

    public function creditCards()
    {
        return $this->hasMany(CreditCard::class);
    }

    public function transactionSeries()
    {
        return $this->hasMany(TransactionSeries::class);
    }

    public function transactions()
    {
        return $this->hasMany(TransactionOccurrence::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function financialGoals()
    {
        return $this->hasMany(FinancialGoal::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    public function onboardingEvents()
    {
        return $this->hasMany(OnboardingEvent::class);
    }

    public function supportFeedback()
    {
        return $this->hasMany(SupportFeedback::class);
    }

    public function onboardingProgress()
    {
        return $this->hasMany(OnboardingProgress::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function financialNotifications()
    {
        return $this->hasMany(FinancialNotification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
