<?php

namespace App\Filament\Resources\CandidateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CandidateDecisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'candidateDecisions';

    protected static ?string $title = 'Решения заявителей';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Заявитель')
                    ->relationship('user', 'full_name')
                    ->default(auth()->id())
                    ->required(),
                Forms\Components\Select::make('decision')
                    ->label('Решение')
                    ->options([
                        'reject' => 'Отклонить',
                        'reserve' => 'В резерв',
                        'interview' => 'Собеседование',
                        'other_vacancy' => 'Другая вакансия',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('decision_date')
                    ->label('Дата решения')
                    ->default(now())
                    ->required()
                    ->native(false),
                Forms\Components\Textarea::make('comment')
                    ->label('Комментарий')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('decision')
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Заявитель')
                    ->sortable(),
                Tables\Columns\TextColumn::make('decision')
                    ->label('Решение')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'reject' => 'Отклонить',
                        'reserve' => 'В резерв',
                        'interview' => 'Собеседование',
                        'other_vacancy' => 'Другая вакансия',
                        default => $state
                    })
                    ->colors([
                        'reject' => 'danger',
                        'reserve' => 'warning',
                        'interview' => 'success',
                        'other_vacancy' => 'info',
                    ]),
                Tables\Columns\TextColumn::make('decision_date')
                    ->label('Дата решения')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->comment;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('decision')
                    ->label('Решение')
                    ->options([
                        'reject' => 'Отклонить',
                        'reserve' => 'В резерв',
                        'interview' => 'Собеседование',
                        'other_vacancy' => 'Другая вакансия',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить решение')
                    ->after(function ($record) {
                        // Автоматически создаем запись в истории статусов при создании решения
                        $candidate = $this->getOwnerRecord();
                        
                        $newStatus = match($record->decision) {
                            'reject' => 'rejected',
                            'reserve' => 'in_reserve',
                            'interview' => 'approved_for_interview',
                            'other_vacancy' => 'in_reserve',
                            default => $candidate->status
                        };

                        $candidate->candidateStatusHistory()->create([
                            'status' => $newStatus,
                            'comment' => "Решение заявителя: {$record->decision_display}. " . ($record->comment ?? ''),
                            'changed_by_id' => $record->user_id,
                            'previous_status' => $candidate->status,
                        ]);

                        // Обновляем статус кандидата
                        $candidate->update(['status' => $newStatus]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('decision_date', 'desc');
    }
}
