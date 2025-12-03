<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Activitylog\Models\Activity;

class ActivityStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        $today = Activity::whereDate('created_at', today())->count();
        $yesterday = Activity::whereDate('created_at', today()->subDay())->count();
        $thisMonth = Activity::whereMonth('created_at', now()->month)->count();
        
        return [
            Stat::make('Изменений сегодня', $today)
                ->description($yesterday . ' вчера')
                ->descriptionIcon($today > $yesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($today > $yesterday ? 'success' : 'danger'),
                
            Stat::make('Изменений за месяц', $thisMonth)
                ->description(now()->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-calendar-days'),
                
            Stat::make('Назначений', Activity::where('subject_type', 'App\Models\Assignment')->count())
                ->description('Изменения назначений')
                ->descriptionIcon('heroicon-m-clipboard-document-list'),
                
            Stat::make('Пользователей', Activity::where('subject_type', 'App\Models\User')->count())
                ->description('Изменения пользователей')
                ->descriptionIcon('heroicon-m-user'),
        ];
    }
}