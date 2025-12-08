<?php

namespace App\Filament\Resources\MassPersonnelReportResource\RelationManagers;

use App\Models\ContractorWorker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractorWorkersRelationManager extends RelationManager
{
    protected static string $relationship = 'contractorWorkers';

    protected static ?string $title = 'Работники массового персонала';
    protected static ?string $modelLabel = 'работник';
    protected static ?string $pluralModelLabel = 'работники';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('full_name')
                    ->label('ФИО работника')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Примечания')
                    ->rows(3)
                    ->maxLength(65535)
                    ->nullable()
                    ->columnSpanFull(),
                    
                Forms\Components\TextInput::make('calculated_hours')
                    ->label('Рассчитанные часы')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.5)
                    ->suffix('ч.')
                    ->helperText('Часы с округлением до 30 минут (0.5 ч)')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО работника')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('calculated_hours')
                    ->label('Часы')
                    ->numeric(2)
                    ->sortable()
                    ->suffix(' ч.')
                    ->alignRight()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold')
                    ->color('success'),
                    
                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Подтвержден')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('photo_missing_reason')
                    ->label('Причина отсутствия фото')
                    ->limit(30)
                    ->toggleable()
                    ->tooltip(fn (ContractorWorker $record) => $record->photo_missing_reason),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('Примечания')
                    ->limit(30)
                    ->toggleable()
                    ->tooltip(fn (ContractorWorker $record) => $record->notes),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_confirmed')
                    ->label('Только подтвержденные')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('is_confirmed', true)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить работника'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Подтверждение работника')
                    ->modalDescription('Вы уверены, что хотите подтвердить этого работника?')
                    ->form([
                        Forms\Components\TextInput::make('photo_missing_reason')
                            ->label('Причина отсутствия фото')
                            ->maxLength(255)
                            ->required()
                            ->helperText('Укажите причину, если нет фото для подтверждения работы'),
                    ])
                    ->action(function (ContractorWorker $record, array $data): void {
                        $record->confirm(auth()->id(), $data['photo_missing_reason']);
                    })
                    ->visible(fn (ContractorWorker $record) => !$record->is_confirmed),
                    
                Tables\Actions\Action::make('unconfirm')
                    ->label('Снять подтверждение')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Снятие подтверждения')
                    ->modalDescription('Вы уверены, что хотите снять подтверждение с этого работника?')
                    ->action(fn (ContractorWorker $record) => $record->unconfirm())
                    ->visible(fn (ContractorWorker $record) => $record->is_confirmed),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_confirm')
                        ->label('Подтвердить выбранных')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('photo_missing_reason')
                                ->label('Причина отсутствия фото (для всех)')
                                ->maxLength(255)
                                ->required()
                                ->helperText('Укажите причину, если нет фото для подтверждения работы'),
                        ])
                        ->action(function ($records, array $data): void {
                            foreach ($records as $record) {
                                $record->confirm(auth()->id(), $data['photo_missing_reason']);
                            }
                        }),
                        
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранных'),
                ]),
            ]);
    }
}
