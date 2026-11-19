<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 */
class TransactionSeries extends Model
{
    protected $fillable = ['type', 'amount', 'description', 'merchant', 'notes', 'category_id', 'financial_account_id', 'credit_card_id', 'recurrence', 'recurrence_day', 'starts_on', 'purchase_date', 'ends_on', 'installments', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'starts_on' => 'date', 'purchase_date' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function occurrences()
    {
        return $this->hasMany(TransactionOccurrence::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
