<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsPanel extends Component
{
    public function markAsRead(string $id): void
    {
        Auth::user()
            ->unreadNotifications()
            ->where('id', $id)
            ->first()
            ?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.admin.notifications-panel', [
            'notifications' => Auth::user()->notifications()->latest()->limit(50)->get(),
        ]);
    }
}
