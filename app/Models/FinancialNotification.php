<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialNotification extends Model
{
    protected $fillable = ['user_id', 'fingerprint', 'title', 'text', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
