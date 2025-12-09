<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmploymentHistoryResource\Pages;
use App\Filament\Resources\EmploymentHistoryResource\RelationManagers;
use App\Models\EmploymentHistory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmploymentHistoryResource extends Resource
{
    protected static ?string $model = EmploymentHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = '👥 Управление персоналом';
    protected static ?string $navigationLabel = 'История трудоустройства';
    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'запись трудоустройства';
    protected static ?string $pluralModelLabel = 'История трудоустройства';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Сотрудник')
                            ->relationship('user', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\Select::make('employment_form')
                            ->label('Форма трудоустройства')
                            ->options([
                                'permanent' => 'Постоянная',
                                'temporary' => 'Временная',
                            ])
                            ->required(),
                            
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
                    ])->columns(2),
                    
                Forms\Components\Section::make('Период работы')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required()
                            ->native(false),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата окончания')
                            ->native(false),
                            
                        Forms\Components\Select::make('termination_reason')
                            ->label('Причина увольнения')
                            ->options([
                                'contract_end' => 'Окончание контракта',
                                'dismissal' => 'Увольнение',
                                'transfer' => 'Перевод',
                                'converted_to_permanent' => 'Перевод на постоянную работу',
                            ])
                            ->nullable(),
                            
                        Forms\Components\DatePicker::make('termination_date')
                            ->label('Дата увольнения')
                            ->native(false),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Условия работы')
                    ->schema([
                        Forms\Components\Select::make('contract_type_id')
                            ->label('Тип договора')
                            ->relationship('contractType', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                            
                        Forms\Components\Select::make('tax_status_id')
                            ->label('Налоговый статус')
                            ->relationship('taxStatus', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                            
                        Forms\Components\Select::make('payment_type')
                            ->label('Тип оплаты')
                            ->options([
                                'salary' => 'Оклад',
                                'rate' => 'Ставка',
                            ])
                            ->required(),
                            
                        Forms\Components\TextInput::make('salary_amount')
                            ->label('Сумма оклада/ставки')
                            ->numeric()
                            ->prefix('₽')
                            ->nullable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Дополнительные условия')
                    ->schema([
                        Forms\Components\Toggle::make('has_overtime')
                            ->label('Сверхурочные')
                            ->required(),
                            
                        Forms\Components\TextInput::make('overtime_rate')
                            ->label('Ставка сверхурочных')
                            ->numeric()
                            ->prefix('₽')
                            ->nullable()
                            ->visible(fn (callable $get) => $get('has_overtime')),
                            
                        Forms\Components\Select::make('work_schedule')
                            ->label('График работы')
                            ->options([
                                '5/2' => '5/2',
                                '2/2' => '2/2', 
                                'piecework' => 'Сдельный',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('primary_specialty_id')
                            ->label('Основная специальность')
                            ->relationship('primarySpecialty', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Примечания')
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('created_by_id')
                            ->label('Создано')
                            ->relationship('createdBy', 'name')
                            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->full_name)
                            ->default(auth()->id())
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Сотрудник')
                    ->formatStateUsing(fn ($state, EmploymentHistory $record) => $record->user->full_name)
                    ->sortable(['surname', 'name'])
                    ->searchable(['users.name', 'users.surname', 'users.patronymic']),
                    
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Отдел')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('position')
                    ->label('Должность')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('employment_form')
                    ->label('Форма')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'permanent' => 'Постоянная',
                        'temporary' => 'Временная',
                        default => $state
                    })
                    ->colors([
                        'permanent' => 'success',
                        'temporary' => 'warning',
                    ]),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Начало')
                    ->date('d.m.Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Окончание')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('—'),
                    
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Оплата')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'salary' => 'Оклад',
                        'rate' => 'Ставка',
                        default => $state
                    }),
                    
                Tables\Columns\TextColumn::make('salary_amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('has_overtime')
                    ->label('Сверхурочные')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->label('Сотрудник')
                    ->relationship('user', 'full_name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('department')
                    ->label('Отдел')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('employment_form')
                    ->label('Форма трудоустройства')
                    ->options([
                        'permanent' => 'Постоянная',
                        'temporary' => 'Временная',
                    ]),
                    
                Tables\Filters\Filter::make('active')
                    ->label('Активные')
                    ->query(fn ($query) => $query->whereNull('end_date')),
                    
                Tables\Filters\Filter::make('historical')
                    ->label('Исторические')
                    ->query(fn ($query) => $query->whereNotNull('end_date')),
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
            ->defaultSort('start_date', 'desc');
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
            'index' => Pages\ListEmploymentHistories::route('/'),
            'create' => Pages\CreateEmploymentHistory::route('/create'),
            'edit' => Pages\EditEmploymentHistory::route('/{record}/edit'),
        ];
    }
}
