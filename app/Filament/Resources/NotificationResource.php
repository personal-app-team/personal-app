<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Filament\Resources\NotificationResource\RelationManagers;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Уведомления';
    protected static ?string $modelLabel = 'уведомление';
    protected static ?string $pluralModelLabel = 'уведомления';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Получатель')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email})"),

                        Forms\Components\Select::make('type')
                            ->label('Тип уведомления')
                            ->options([
                                'trainee_request' => 'Запрос на стажировку',
                                'trainee_expiring' => 'Стажировка истекает',
                                'trainee_decision' => 'Требуется решение по стажеру',
                                'trainee_blocked' => 'Стажер заблокирован',
                                'system' => 'Системное уведомление',
                                'assignment' => 'Назначение',
                                'shift' => 'Смена',
                                'work_request' => 'Заявка',
                                'test' => 'Тестовое уведомление',
                            ])
                            ->required()
                            ->default('system'),

                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Введите заголовок уведомления'),

                        Forms\Components\Textarea::make('message')
                            ->label('Сообщение')
                            ->required()
                            ->rows(4)
                            ->placeholder('Введите текст уведомления')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Связь с другими сущностями')
                    ->description('Необязательно - для привязки уведомления к конкретной записи')
                    ->schema([
                        Forms\Components\Select::make('related_type')
                            ->label('Тип связанной сущности')
                            ->options([
                                'App\Models\TraineeRequest' => 'Запрос на стажировку',
                                'App\Models\Assignment' => 'Назначение',
                                'App\Models\Shift' => 'Смена',
                                'App\Models\WorkRequest' => 'Заявка',
                                'App\Models\User' => 'Пользователь',
                            ])
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn ($set) => $set('related_id', null)),

                        Forms\Components\Select::make('related_id')
                            ->label('Связанная запись')
                            ->options(function ($get) {
                                $relatedType = $get('related_type');
                                
                                if (!$relatedType) {
                                    return [];
                                }

                                return $relatedType::pluck('id', 'id')->map(function ($id) use ($relatedType) {
                                    $model = $relatedType::find($id);
                                    return $model ? "ID: {$id} - " . ($model->name ?? $model->title ?? $model->candidate_name ?? 'Запись') : "ID: {$id}";
                                });
                            })
                            ->searchable()
                            ->visible(fn ($get) => !empty($get('related_type'))),
                    ])->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('data')
                            ->label('Данные (JSON)')
                            ->rows(3)
                            ->helperText('Дополнительные данные в формате JSON')
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('read_at')
                            ->label('Прочитано')
                            ->nullable()
                            ->helperText('Оставьте пустым для непрочитанного уведомления'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                ->label('Получатель')
                ->searchable()
                ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'trainee_request' => 'Стажировка',
                        'trainee_expiring' => 'Истекает',
                        'trainee_decision' => 'Решение',
                        'trainee_blocked' => 'Блокировка',
                        'system' => 'Система',
                        'assignment' => 'Назначение',
                        'shift' => 'Смена',
                        'work_request' => 'Заявка',
                        'test' => 'Тест',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'trainee_request' => 'primary',
                        'trainee_expiring' => 'warning',
                        'trainee_decision' => 'danger',
                        'trainee_blocked' => 'danger',
                        'system' => 'gray',
                        'assignment' => 'info',
                        'shift' => 'success',
                        'work_request' => 'info',
                        'test' => 'gray',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('message')
                    ->label('Сообщение')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->message)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('read_at')
                    ->label('Статус')
                    ->getStateUsing(fn ($record) => !is_null($record->read_at))
                    ->boolean()
                    ->trueIcon('heroicon-o-check')
                    ->falseIcon('heroicon-o-bell')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->label('Прочитано'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_read')
                    ->label('Отметить прочитанным')
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->visible(fn (Notification $record) => is_null($record->read_at))
                    ->action(fn (Notification $record) => $record->markAsRead()),

                Tables\Actions\Action::make('mark_as_unread')
                    ->label('Отметить непрочитанным')
                    ->icon('heroicon-o-bell')
                    ->color('gray')
                    ->visible(fn (Notification $record) => !is_null($record->read_at))
                    ->action(fn (Notification $record) => $record->markAsUnread()),

                Tables\Actions\ViewAction::make()
                    ->label('Просмотр'),

                Tables\Actions\DeleteAction::make()
                    ->label('Удалить')
                    ->visible(fn () => auth()->user()->can('manage_notifications')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
