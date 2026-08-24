<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCardInvoicePayment extends Model
{
    protected $fillable = ['user_id', 'credit_card_id', 'invoice_month', 'amount', 'paid_at'];

    protected function casts(): array
    {
        return ['invoice_month' => 'date', 'amount' => 'decimal:2', 'paid_at' => 'date'];
    }

    public function card()
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
    }
}
