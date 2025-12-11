<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidateStatusHistoryResource\Pages;
use App\Models\CandidateStatusHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CandidateStatusHistoryResource extends Resource
{
    protected static ?string $model = CandidateStatusHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = '🎯 Подбор персонала';
    protected static ?string $navigationLabel = 'История статусов';
    protected static ?int $navigationSort = 80;

    protected static ?string $modelLabel = 'запись истории статуса';
    protected static ?string $pluralModelLabel = 'История статусов кандидатов';

    // Скрываем из навигации, доступ через RelationManager
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('candidate_id')
                            ->label('Кандидат')
                            ->relationship('candidate', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'new' => 'Новый',
                                'contacted' => 'Связались',
                                'sent_for_approval' => 'Отправлен на согласование',
                                'approved_for_interview' => 'Одобрен для собеседования',
                                'in_reserve' => 'В резерве',
                                'rejected' => 'Отклонен',
                            ])
                            ->required()
                            ->live(),
                        
                        Forms\Components\Select::make('changed_by_id')
                            ->label('Кто изменил')
                            ->relationship('changedBy', 'full_name')
                            ->default(auth()->id())
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\Hidden::make('previous_status'),
                    ])->columns(2),

                Forms\Components\Section::make('Комментарий')
                    ->schema([
                        Forms\Components\Textarea::make('comment')
                            ->label('Комментарий')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Кандидат')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => CandidateResource::getUrl('edit', [$record->candidate_id]))
                    ->openUrlInNewTab(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'new' => 'Новый',
                        'contacted' => 'Связались',
                        'sent_for_approval' => 'На согласовании',
                        'approved_for_interview' => 'Одобрен',
                        'in_reserve' => 'В резерве',
                        'rejected' => 'Отклонен',
                        default => $state
                    })
                    ->colors([
                        'new' => 'gray',
                        'contacted' => 'info',
                        'sent_for_approval' => 'warning',
                        'approved_for_interview' => 'success',
                        'in_reserve' => 'primary',
                        'rejected' => 'danger',
                    ]),
                
                Tables\Columns\TextColumn::make('previous_status')
                    ->label('Предыдущий статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'new' => 'Новый',
                        'contacted' => 'Связались',
                        'sent_for_approval' => 'На согласовании',
                        'approved_for_interview' => 'Одобрен',
                        'in_reserve' => 'В резерве',
                        'rejected' => 'Отклонен',
                        null => '—',
                        default => $state
                    })
                    ->colors([
                        'new' => 'gray',
                        'contacted' => 'info',
                        'sent_for_approval' => 'warning',
                        'approved_for_interview' => 'success',
                        'in_reserve' => 'primary',
                        'rejected' => 'danger',
                    ])
                    ->placeholder('—'),
                
                Tables\Columns\TextColumn::make('changedBy.full_name')
                    ->label('Кто изменил')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->comment)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата изменения')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Новый',
                        'contacted' => 'Связались',
                        'sent_for_approval' => 'Отправлен на согласование',
                        'approved_for_interview' => 'Одобрен для собеседования',
                        'in_reserve' => 'В резерве',
                        'rejected' => 'Отклонен',
                    ]),
                
                Tables\Filters\SelectFilter::make('candidate_id')
                    ->label('Кандидат')
                    ->relationship('candidate', 'full_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('changed_by_id')
                    ->label('Кто изменил')
                    ->relationship('changedBy', 'full_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\Filter::make('created_at')
                    ->label('Дата изменения')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('until')
                            ->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when($data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_candidate')
                    ->label('К кандидату')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => CandidateResource::getUrl('edit', [$record->candidate_id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Нет записей истории статусов')
            ->emptyStateDescription('Записи истории создаются автоматически при изменении статуса кандидата.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать запись истории'),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers если понадобятся
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCandidateStatusHistories::route('/'),
            'create' => Pages\CreateCandidateStatusHistory::route('/create'),
            'edit' => Pages\EditCandidateStatusHistory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['candidate', 'changedBy'])
            ->latest();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
