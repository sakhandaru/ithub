<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Notifications', ['notifications' => $request->user()->notifications()->latest()->limit(50)->get()->map(fn ($notification): array => ['id' => $notification->id, 'read_at' => $notification->read_at?->toIso8601String(), 'created_at' => $notification->created_at?->toIso8601String(), ...$notification->data])->values()]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return to_route('notifications.index');
    }
}
