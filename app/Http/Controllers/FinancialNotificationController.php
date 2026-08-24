<?php

namespace App\Http\Controllers;

use App\Services\FinancialNotificationService;
use Illuminate\Http\RedirectResponse;

class FinancialNotificationController extends Controller
{
    public function read(int $id, FinancialNotificationService $service): RedirectResponse
    {
        $service->markRead(auth()->user(), $id);

        return back();
    }

    public function readAll(FinancialNotificationService $service): RedirectResponse
    {
        $service->markAllRead(auth()->user());

        return back();
    }
}
