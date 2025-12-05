<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Система';
    protected static ?string $navigationLabel = 'История изменений';
    protected static ?int $navigationSort = 100;
    
    protected static ?string $modelLabel = 'запись истории';
    protected static ?string $pluralModelLabel = 'История изменений';
    
    public static function canViewAny(): bool
    {
        return true;
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDate('created_at', '>=', Carbon::now()->subYear()->toDateString())
            ->orderBy('created_at', 'desc');
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Действие')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Тип объекта')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'App\\Models\\Assignment' => '📋 Назначение',
                        'App\\Models\\User' => '👤 Пользователь',
                        'App\\Models\\Shift' => '💰 Смена',
                        'App\\Models\\WorkRequest' => '📄 Заявка',
                        'App\\Models\\Compensation' => '💸 Компенсация',
                        'App\\Models\\ShiftExpense' => '🧾 Расход смены',
                        'App\\Models\\Contractor' => '🏢 Подрядчик',
                        default => class_basename($state),
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID объекта'),
                    
                Tables\Columns\TextColumn::make('event')
                    ->label('Событие')
                    ->badge()
                    ->colors([
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'created' => 'Создание',
                        'updated' => 'Изменение',
                        'deleted' => 'Удаление',
                        'restored' => 'Восстановление',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Тип объекта')
                    ->options([
                        'App\\Models\\Assignment' => '📋 Назначения',
                        'App\\Models\\User' => '👤 Пользователи',
                        'App\\Models\\Shift' => '💰 Смены',
                        'App\\Models\\WorkRequest' => '📄 Заявки',
                        'App\\Models\\Compensation' => '💸 Компенсации',
                        'App\\Models\\ShiftExpense' => '🧾 Расходы смен',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('event')
                    ->label('Событие')
                    ->options([
                        'created' => 'Создание',
                        'updated' => 'Изменение',
                        'deleted' => 'Удаление',
                        'restored' => 'Восстановление',
                    ]),
                    
                Tables\Filters\Filter::make('created_at')
                    ->label('Дата создания')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Детали записи')
                    ->modalContent(fn ($record) => view(
                        'filament.resources.activity-log-resource.components.log-details',
                        ['log' => $record]
                    )),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
