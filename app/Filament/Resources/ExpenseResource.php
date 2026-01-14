<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use App\Filament\Resources\ShiftResource;
use App\Filament\Resources\MassPersonnelReportResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Расходы';
    protected static ?string $modelLabel = 'расход';
    protected static ?string $pluralModelLabel = 'Расходы';
    protected static ?string $navigationGroup = '💰 Финансы';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Объект расхода')
                    ->schema([
                        Forms\Components\Select::make('expensable_type')
                            ->label('Тип объекта')
                            ->options([
                                'App\\Models\\Shift' => '📋 Смена',
                                'App\\Models\\MassPersonnelReport' => '👥 Отчет по массовому персоналу',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('expensable_id', null))
                            ->helperText('Выберите тип объекта, к которому относится расход'),
                            
                        Forms\Components\Select::make('expensable_id')
                            ->label('Объект')
                            ->searchable()
                            ->required()
                            ->options(function (callable $get) {
                                $type = $get('expensable_type');
                                
                                if (!$type) {
                                    return [];
                                }
                                
                                return match($type) {
                                    'App\\Models\\Shift' => \App\Models\Shift::query()
                                        ->with('workRequest')
                                        ->get()
                                        ->mapWithKeys(fn ($shift) => [
                                            $shift->id => "Смена #{$shift->id} (" . ($shift->workRequest ? $shift->workRequest->title : 'Без заявки') . ")"
                                        ]),
                                    'App\\Models\\MassPersonnelReport' => \App\Models\MassPersonnelReport::query()
                                        ->get()
                                        ->mapWithKeys(fn ($report) => [
                                            $report->id => "Отчет #{$report->id} ({$report->workers_count} чел.)"
                                        ]),
                                    default => [],
                                };
                            })
                            ->helperText('Выберите конкретный объект'),
                    ]),
                    
                Forms\Components\Section::make('Информация о расходе')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Тип расхода')
                            ->options(Expense::getTypeOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state !== 'custom') {
                                    $set('custom_type', null);
                                }
                            })
                            ->helperText('Выберите тип расхода или создайте свой'),
                            
                        Forms\Components\TextInput::make('custom_type')
                            ->label('Название пользовательского типа')
                            ->maxLength(255)
                            ->visible(fn (callable $get) => $get('type') === 'custom')
                            ->required(fn (callable $get) => $get('type') === 'custom')
                            ->helperText('Введите название нового типа расхода'),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма (руб)')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('₽')
                            ->helperText('Введите сумму расхода'),
                            
                        Forms\Components\FileUpload::make('receipt_photo')
                            ->label('Фото чека')
                            ->image()
                            ->directory('expenses/receipts')
                            ->maxSize(5120)
                            ->helperText('Максимальный размер: 5MB')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg']),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Подробное описание расхода...')
                            ->helperText('Опишите, на что был потрачен бюджет'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expensable_type')
                    ->label('Тип объекта')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'App\\Models\\Shift' => '📋 Смена',
                        'App\\Models\\MassPersonnelReport' => '👥 Отчет',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'App\\Models\\Shift',
                        'info' => 'App\\Models\\MassPersonnelReport',
                    ])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('expensable_id')
                    ->label('ID объекта')
                    ->sortable()
                    ->searchable()
                    ->url(fn (Expense $record) => match($record->expensable_type) {
                        'App\\Models\\Shift' => ShiftResource::getUrl('edit', [$record->expensable_id]),
                        'App\\Models\\MassPersonnelReport' => MassPersonnelReportResource::getUrl('edit', [$record->expensable_id]),
                        default => null,
                    })
                    ->openUrlInNewTab(),
                    
                Tables\Columns\TextColumn::make('type_display')
                    ->label('Тип расхода')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        str_contains($state, '🚕') => 'warning',
                        str_contains($state, '🛠️') => 'info',
                        str_contains($state, '🍔') => 'success',
                        str_contains($state, '🏨') => 'danger',
                        str_contains($state, '📄') => 'gray',
                        str_contains($state, '📝') => 'primary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable()
                    ->alignRight()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn (Expense $record) => $record->description),
                    
                Tables\Columns\IconColumn::make('receipt_photo')
                    ->label('Чек')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (Expense $record): string => $record->receipt_photo ? 'Есть фото чека' : 'Нет фото чека'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expensable_type')
                    ->label('Тип объекта')
                    ->options([
                        'App\\Models\\Shift' => 'Смена',
                        'App\\Models\\MassPersonnelReport' => 'Отчет по массовому персоналу',
                    ]),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип расхода')
                    ->options(Expense::getTypeOptions()),
                    
                Tables\Filters\Filter::make('has_receipt')
                    ->label('С чеком')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('receipt_photo')),
                    
                Tables\Filters\Filter::make('custom_types')
                    ->label('Пользовательские типы')
                    ->toggle()
                    ->query(fn ($query) => $query->where('type', 'custom')),
                    
                Tables\Filters\Filter::make('amount_range')
                    ->label('Сумма')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('От')
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('До')
                            ->numeric()
                            ->prefix('₽'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('view_receipt')
                    ->label('Просмотр чека')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Expense $record) => $record->receipt_photo ? asset('storage/' . $record->receipt_photo) : null)
                    ->openUrlInNewTab()
                    ->hidden(fn (Expense $record) => !$record->receipt_photo),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->emptyStateHeading('Нет расходов')
            ->emptyStateDescription('Создайте первый расход.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать расход'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            // ... другие RelationManagers
            RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
