<?php

namespace App\Filament\Resources\CandidateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InterviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'interviews';

    protected static ?string $title = 'Собеседования';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Forms\Components\Hidden::make('created_by_id')
                    ->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('scheduled_at')
            ->columns([
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Запланировать собеседование')
                    ->after(function ($record) {
                        // После создания собеседования обновляем статус кандидата
                        $this->getOwnerRecord()->update(['status' => 'approved_for_interview']);
                    }),
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
                    ->action(function ($record, array $data) {
                        $record->complete($data['result'], $data['feedback']);
                    })
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'scheduled'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }
}
