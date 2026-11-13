<?php

use App\Models\User;
use App\Notifications\TaskNotification;

test('users can filter unread notifications and mark all as read', function () {
    $user = User::factory()->create();
    $user->notify(new TaskNotification('task', 'Update pertama', 'Task pertama berubah.', '/'));
    $user->notifications()->firstOrFail()->markAsRead();
    $user->notify(new TaskNotification('task', 'Update kedua', 'Task kedua berubah.', '/'));

    $this->actingAs($user)
        ->get(route('notifications.index').'?unread=1')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->where('unread', true)
            ->where('unreadCount', 1)
            ->has('notifications', 1)
            ->where('notifications.0.title', 'Update kedua'));

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirect(route('notifications.index'));

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});
