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
        $unread = $request->boolean('unread');
        $notifications = $request->user()->notifications()
            ->when($unread, fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('Notifications', [
            'notifications' => $notifications->map(fn ($notification): array => ['id' => $notification->id, 'read_at' => $notification->read_at?->toIso8601String(), 'created_at' => $notification->created_at?->toIso8601String(), ...$notification->data])->values(),
            'unread' => $unread,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return to_route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return to_route('notifications.index');
    }
}
