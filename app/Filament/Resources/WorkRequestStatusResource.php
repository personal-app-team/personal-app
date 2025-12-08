<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkRequestStatusResource\Pages;
use App\Filament\Resources\WorkRequestStatusResource\RelationManagers;
use App\Models\WorkRequestStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkRequestStatusResource extends Resource
{
    protected static ?string $model = WorkRequestStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Заявки на работы';
    protected static ?string $navigationLabel = 'История статусов';
    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'статус заявки';
    protected static ?string $pluralModelLabel = 'История статусов заявок';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'История статусов заявок',
            'create' => 'Создать запись статуса',
            'edit' => 'Редактировать запись статуса',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('work_request_id')
                            ->label('Заявка на работу')
                            ->relationship('workRequest', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Выберите заявку, для которой изменяется статус'),
                            
                        Forms\Components\Textarea::make('status')
                            ->label('Статус')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->rows(3)
                            ->helperText('Текст статуса (например: "В работе", "Завершена", "Отменена")'),
                    ]),
                    
                Forms\Components\Section::make('Информация об изменении')
                    ->schema([
                        Forms\Components\DateTimePicker::make('changed_at')
                            ->label('Время изменения')
                            ->required()
                            ->default(now())
                            ->helperText('Когда был изменен статус'),
                            
                        Forms\Components\Select::make('changed_by_id')
                            ->label('Изменено пользователем')
                            ->relationship('changedBy', 'full_name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Кто изменил статус (автоматически заполняется системой)'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->maxLength(65535)
                            ->nullable()
                            ->columnSpanFull()
                            ->rows(3)
                            ->helperText('Дополнительные комментарии по изменению статуса'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('workRequest.id')
                    ->label('Заявка')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => $record->workRequest ? 
                        \App\Filament\Resources\WorkRequestResource::getUrl('edit', [$record->workRequest->id]) : null
                    )
                    ->openUrlInNewTab()
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->status;
                    })
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('changed_at')
                    ->label('Время изменения')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('changedBy.full_name')
                    ->label('Изменено')
                    ->searchable()
                    ->placeholder('Система')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('Заметки')
                    ->searchable()
                    ->limit(30)
                    ->toggleable()
                    ->tooltip(function ($record) {
                        return $record->notes;
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('work_request_id')
                    ->label('Заявка')
                    ->relationship('workRequest', 'id')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('changed_at')
                    ->label('Дата изменения')
                    ->form([
                        Forms\Components\DatePicker::make('changed_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('changed_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['changed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('changed_at', '>=', $date),
                            )
                            ->when(
                                $data['changed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('changed_at', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\Filter::make('has_notes')
                    ->label('📝 Есть заметки')
                    ->query(fn ($query) => $query->whereNotNull('notes')->where('notes', '!=', '')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->emptyStateHeading('Нет истории статусов')
            ->emptyStateDescription('Создайте первую запись истории статусов.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать запись статуса'),
            ])
            ->defaultSort('changed_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkRequestStatuses::route('/'),
            'create' => Pages\CreateWorkRequestStatus::route('/create'),
            'edit' => Pages\EditWorkRequestStatus::route('/{record}/edit'),
        ];
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'initiator', 'dispatcher']);
    }
}
