<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
    protected $fillable = ['name', 'brand', 'bin', 'issuer', 'country', 'card_type', 'metadata', 'financial_account_id', 'closing_day', 'due_day', 'limit', 'color', 'is_archived'];

    protected function casts(): array
    {
        return ['limit' => 'decimal:2', 'metadata' => 'array', 'is_archived' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(TransactionOccurrence::class, 'credit_card_id');
    }

    public function invoicePayments()
    {
        return $this->hasMany(CreditCardInvoicePayment::class);
    }
}
