<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TransferService
{
    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Transfer
    {
        $from = (int) $data['from_account_id'];
        $to = (int) $data['to_account_id'];
        if ($from === $to) {
            throw new \InvalidArgumentException('A conta de origem e a conta de destino devem ser diferentes.');
        }

        if (! $user->accounts()->whereKey($from)->exists() || ! $user->accounts()->whereKey($to)->exists()) {
            abort(403);
        }

        $status = $data['status'] ?? 'settled';

        return $user->transfers()->create([
            'from_account_id' => $from,
            'to_account_id' => $to,
            'amount' => $data['amount'],
            'transfer_date' => $data['transfer_date'],
            'description' => $data['description'] ?? null,
            'status' => $status,
            'settled_at' => $status === 'settled' ? ($data['settled_at'] ?? today()) : null,
        ]);
    }

    public function toggleSettled(Transfer $transfer): void
    {
        if (auth()->check()) {
            Gate::forUser(auth()->user())->authorize('update', $transfer);
        }

        if ($transfer->status === 'settled') {
            $transfer->update(['status' => 'pending', 'settled_at' => null]);

            return;
        }

        $transfer->update(['status' => 'settled', 'settled_at' => today()]);
    }

    public function cancel(Transfer $transfer): void
    {
        if (auth()->check()) {
            Gate::forUser(auth()->user())->authorize('update', $transfer);
        }
        $transfer->update(['status' => 'canceled', 'settled_at' => null]);
    }
}
