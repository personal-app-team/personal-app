<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\Builder;

class NotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationGroup = '👑 Система';
    protected static ?string $navigationLabel = 'Уведомления';
    protected static ?int $navigationSort = 70;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->label('ID')
                    ->disabled(),
                    
                Forms\Components\TextInput::make('type')
                    ->label('Тип уведомления')
                    ->disabled(),
                    
                Forms\Components\TextInput::make('notifiable_type')
                    ->label('Тип получателя')
                    ->disabled(),
                    
                Forms\Components\TextInput::make('notifiable_id')
                    ->label('ID получателя')
                    ->disabled(),
                    
                Forms\Components\KeyValue::make('data')
                    ->label('Данные уведомления')
                    ->columnSpanFull()
                    ->disabled(),
                    
                Forms\Components\DateTimePicker::make('read_at')
                    ->label('Прочитано')
                    ->disabled(),
                    
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Создано')
                    ->disabled(),
                    
                Forms\Components\DateTimePicker::make('updated_at')
                    ->label('Обновлено')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('data.title')
                    ->label('Заголовок')
                    ->limit(30)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('data.message')
                    ->label('Сообщение')
                    ->limit(50)
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('unread')
                    ->label('Только непрочитанные')
                    ->query(fn ($query) => $query->whereNull('read_at')),
                    
                Tables\Filters\Filter::make('read')
                    ->label('Только прочитанные')
                    ->query(fn ($query) => $query->whereNotNull('read_at')),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип уведомления')
                    ->options(function () {
                        $types = DatabaseNotification::select('type')->distinct()->get();
                        $options = [];
                        foreach ($types as $type) {
                            $options[$type->type] = class_basename($type->type);
                        }
                        return $options;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->hidden(fn ($record) => !auth()->user()->can('view', $record)),
                
                Tables\Actions\Action::make('markAsRead')
                    ->label('Отметить прочитанным')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (DatabaseNotification $record) {
                        $record->markAsRead();
                    })
                    ->visible(fn (DatabaseNotification $record) => 
                        is_null($record->read_at) && 
                        auth()->user()->can('markAsRead', $record) // Используем наш метод
                    ),
                    
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => !auth()->user()->can('delete', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn () => !auth()->user()->can('deleteAny', DatabaseNotification::class)),
                    Tables\Actions\BulkAction::make('markAsRead')
                        ->label('Отметить выбранные как прочитанные')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->markAsRead();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->hidden(fn () => !auth()->user()->can('viewAny', DatabaseNotification::class)),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        
        // Если пользователь не админ и не имеет разрешение view_any_notification,
        // показываем только его уведомления
        if (!auth()->user()->hasRole('admin') && !auth()->user()->can('viewAny', DatabaseNotification::class)) {
            $query->where('notifiable_type', 'App\Models\User')
                ->where('notifiable_id', auth()->id());
        }
        
        return $query;
    }
}
