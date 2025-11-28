<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmploymentHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'employmentHistory';

    protected static ?string $recordTitleAttribute = 'position';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employment_form')
                    ->label('Форма занятости')
                    ->options([
                        'permanent' => 'Постоянная',
                        'temporary' => 'Временная',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\Select::make('department_id')
                    ->label('Отдел')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('position')
                    ->label('Должность')
                    ->required()
                    ->maxLength(255),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Дата начала')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Дата окончания')
                    ->visible(fn (callable $get) => $get('employment_form') === 'temporary'),

                Forms\Components\Select::make('termination_reason')
                    ->label('Причина окончания')
                    ->options([
                        'contract_end' => 'Окончание контракта',
                        'dismissal' => 'Увольнение',
                        'transfer' => 'Перевод',
                        'converted_to_permanent' => 'Перевод в постоянные',
                    ])
                    ->visible(fn (callable $get) => $get('end_date') !== null),

                Forms\Components\DatePicker::make('termination_date')
                    ->label('Дата увольнения/окончания')
                    ->visible(fn (callable $get) => $get('end_date') !== null),

                Forms\Components\Select::make('contract_type_id')
                    ->label('Тип договора')
                    ->relationship('contractType', 'name')
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('tax_status_id')
                    ->label('Налоговый статус')
                    ->relationship('taxStatus', 'name')
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('payment_type')
                    ->label('Тип оплаты')
                    ->options([
                        'salary' => 'Оклад',
                        'rate' => 'Тариф',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\TextInput::make('salary_amount')
                    ->label('Размер оклада')
                    ->numeric()
                    ->visible(fn (callable $get) => $get('payment_type') === 'salary')
                    ->suffix('руб.'),

                Forms\Components\Toggle::make('has_overtime')
                    ->label('Учитывать переработки')
                    ->visible(fn (callable $get) => $get('payment_type') === 'salary')
                    ->reactive(),

                Forms\Components\TextInput::make('overtime_rate')
                    ->label('Ставка переработки')
                    ->numeric()
                    ->visible(fn (callable $get) => $get('payment_type') === 'salary' && $get('has_overtime'))
                    ->suffix('руб./час'),

                Forms\Components\Select::make('primary_specialty_id')
                    ->label('Основная специальность')
                    ->relationship('primarySpecialty', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get) => $get('payment_type') === 'rate'),

                Forms\Components\Select::make('work_schedule')
                    ->label('График работы')
                    ->options([
                        '5/2' => '5/2',
                        '2/2' => '2/2',
                        'piecework' => 'Сдельный',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Примечания')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employment_form')
                    ->label('Форма')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'permanent' => '📋 Постоянная',
                        'temporary' => '🕒 Временная',
                    }),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Отдел')
                    ->sortable(),

                Tables\Columns\TextColumn::make('position')
                    ->label('Должность')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Начало')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Окончание')
                    ->date('d.m.Y')
                    ->placeholder('Активно')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Оплата')
                    ->formatStateUsing(fn ($state, $record) => $record->payment_type === 'salary' 
                        ? "Оклад: {$record->salary_amount} руб."
                        : "Тариф: " . ($record->primarySpecialty->base_hourly_rate ?? '0') . " руб./час"),

                Tables\Columns\TextColumn::make('work_schedule')
                    ->label('График'),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Только активные')
                    ->query(fn ($query) => $query->whereNull('end_date')),
                
                Tables\Filters\Filter::make('historical')
                    ->label('Только исторические')
                    ->query(fn ($query) => $query->whereNotNull('end_date')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('terminate')
                    ->label('Завершить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->end_date === null)
                    ->form([
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата окончания')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\Select::make('termination_reason')
                            ->label('Причина')
                            ->options([
                                'contract_end' => 'Окончание контракта',
                                'dismissal' => 'Увольнение',
                                'transfer' => 'Перевод',
                                'converted_to_permanent' => 'Перевод в постоянные',
                            ])
                            ->required(),
                        
                        Forms\Components\DatePicker::make('termination_date')
                            ->label('Дата увольнения')
                            ->default(now()),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Комментарий'),
                    ])
                    ->action(function ($record, $data) {
                        $record->update([
                            'end_date' => $data['end_date'],
                            'termination_reason' => $data['termination_reason'],
                            'termination_date' => $data['termination_date'],
                            'notes' => $data['notes'] . "\n" . $record->notes,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }
}
