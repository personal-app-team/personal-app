<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Кнопка отметить все как прочитанные
        if (Auth::user()->unreadNotifications()->exists()) {
            $actions[] = Actions\Action::make('markAllAsRead')
                ->label('Отметить все как прочитанные')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    Auth::user()->unreadNotifications->each->markAsRead();
                    $this->refresh();
                });
        }
        
        return $actions;
    }
}
