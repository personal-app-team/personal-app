<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.notifications-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public $unreadCount = 0;
    public $recentNotifications = [];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->unreadCount = $user->unreadNotifications()->count();
            $this->recentNotifications = $user->notifications()
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'data' => $notification->data,
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at->diffForHumans(),
                        'is_unread' => is_null($notification->read_at),
                    ];
                })
                ->toArray();
        }
    }

    public function markAsRead(string $id): void
    {
        $user = Auth::user();
        if ($user) {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
                $this->loadNotifications();
            }
        }
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications()->update(['read_at' => now()]);
            $this->loadNotifications();
        }
    }
}
