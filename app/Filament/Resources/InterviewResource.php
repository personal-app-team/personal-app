<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterviewResource\Pages;
use App\Filament\Resources\InterviewResource\RelationManagers;
use App\Models\Interview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InterviewResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Подбор персонала';
    protected static ?string $navigationLabel = 'Собеседования';
    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'собеседование';
    protected static ?string $pluralModelLabel = 'Собеседования';

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
                            ->required(),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Дата и время')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('interview_type')
                            ->label('Тип собеседования')
                            ->options([
                                'technical' => 'Техническое',
                                'managerial' => 'С руководителем',
                                'cultural' => 'Культурное',
                                'combined' => 'Комбинированное',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('location')
                            ->label('Место проведения')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Select::make('interviewer_id')
                            ->label('Собеседующий')
                            ->relationship('interviewer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Длительность (минуты)')
                            ->numeric()
                            ->default(60)
                            ->minValue(1)
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Результаты')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'scheduled' => 'Запланировано',
                                'completed' => 'Завершено',
                                'cancelled' => 'Отменено',
                            ])
                            ->default('scheduled')
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('result')
                            ->label('Результат')
                            ->options([
                                'hire' => 'Нанять',
                                'reject' => 'Отклонить',
                                'reserve' => 'В резерв',
                                'other_vacancy' => 'Другая вакансия',
                                'trainee' => 'Стажировка',
                            ])
                            ->nullable()
                            ->visible(fn (callable $get) => $get('status') === 'completed'),
                        Forms\Components\Textarea::make('feedback')
                            ->label('Отзыв')
                            ->nullable()
                            ->visible(fn (callable $get) => $get('status') === 'completed'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Системная информация')
                    ->schema([
                        Forms\Components\Hidden::make('created_by_id')
                            ->default(auth()->id()),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Дата и время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('interview_type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'technical' => 'Техническое',
                        'managerial' => 'С руководителем',
                        'cultural' => 'Культурное',
                        'combined' => 'Комбинированное',
                        default => $state
                    })
                    ->badge()
                    ->colors([
                        'technical' => 'info',
                        'managerial' => 'warning',
                        'cultural' => 'success',
                        'combined' => 'primary',
                    ]),
                Tables\Columns\TextColumn::make('interviewer.name')
                    ->label('Собеседующий')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'scheduled' => 'Запланировано',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                        default => $state
                    })
                    ->colors([
                        'scheduled' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('result')
                    ->label('Результат')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'hire' => 'Нанять',
                        'reject' => 'Отклонить',
                        'reserve' => 'В резерв',
                        'other_vacancy' => 'Другая вакансия',
                        'trainee' => 'Стажировка',
                        default => '—'
                    })
                    ->colors([
                        'hire' => 'success',
                        'reject' => 'danger',
                        'reserve' => 'warning',
                        'other_vacancy' => 'info',
                        'trainee' => 'primary',
                    ])
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'scheduled' => 'Запланировано',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                    ]),
                Tables\Filters\SelectFilter::make('interview_type')
                    ->label('Тип собеседования')
                    ->options([
                        'technical' => 'Техническое',
                        'managerial' => 'С руководителем',
                        'cultural' => 'Культурное',
                        'combined' => 'Комбинированное',
                    ]),
                Tables\Filters\SelectFilter::make('interviewer')
                    ->label('Собеседующий')
                    ->relationship('interviewer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('scheduled_at')
                    ->label('Предстоящие')
                    ->query(fn (Builder $query) => $query->where('scheduled_at', '>', now())->where('status', 'scheduled')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('Завершить')
                    ->icon('heroicon-o-check')
                    ->form([
                        Forms\Components\Select::make('result')
                            ->label('Результат')
                            ->options([
                                'hire' => 'Нанять',
                                'reject' => 'Отклонить',
                                'reserve' => 'В резерв',
                                'other_vacancy' => 'Другая вакансия',
                                'trainee' => 'Стажировка',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('feedback')
                            ->label('Отзыв')
                            ->nullable(),
                    ])
                    ->action(function (Interview $record, array $data) {
                        $record->complete($data['result'], $data['feedback']);
                    })
                    ->color('success')
                    ->visible(fn (Interview $record) => $record->status === 'scheduled'),
                Tables\Actions\Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn (Interview $record) => $record->cancel())
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Interview $record) => $record->status === 'scheduled'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Нет собеседований')
            ->emptyStateDescription('Запланируйте первое собеседование.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Запланировать собеседование'),
            ])
            ->defaultSort('scheduled_at', 'asc');
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
            'index' => Pages\ListInterviews::route('/'),
            'create' => Pages\CreateInterview::route('/create'),
            'edit' => Pages\EditInterview::route('/{record}/edit'),
        ];
    }
}
