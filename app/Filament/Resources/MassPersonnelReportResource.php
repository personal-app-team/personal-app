<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MassPersonnelReportResource\Pages;
use App\Filament\Resources\MassPersonnelReportResource\RelationManagers;
use App\Models\MassPersonnelReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Filament\Resources\WorkRequestResource;
use App\Filament\Resources\TaxStatusResource;
use App\Filament\Resources\ContractTypeResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\SpecialtyResource;
use App\Filament\Resources\WorkTypeResource;

class MassPersonnelReportResource extends Resource
{
    protected static ?string $model = MassPersonnelReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = '👥 Управление персоналом';
    protected static ?string $navigationLabel = 'Отчеты по массовому персоналу';
    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'отчет по массовому персоналу';
    protected static ?string $pluralModelLabel = 'Отчеты по массовому персоналу';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Отчеты по массовому персоналу',
            'create' => 'Создать отчет по массовому персоналу',
            'edit' => 'Редактировать отчет по массовому персоналу',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('request_id')
                            ->label('Заявка на работу')
                            ->relationship('workRequest', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Выберите заявку на работу, к которой относится отчет'),
                            
                        Forms\Components\TextInput::make('total_hours')
                            ->label('Общее количество часов')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('ч.'),
                    ]), // ← ЗАКРЫВАЕМ СЕКЦИЮ
                        
                Forms\Components\Section::make('Финансовая информация')
                    ->schema([
                        Forms\Components\TextInput::make('base_hourly_rate')
                            ->label('Базовая ставка за час')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('₽')
                            ->suffix('в час'),
                            
                        Forms\Components\TextInput::make('compensation_amount')
                            ->label('Сумма компенсации')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('₽')
                            ->default(0)
                            ->helperText('Дополнительные выплаты (премии, бонусы)'),
                            
                        Forms\Components\Textarea::make('compensation_description')
                            ->label('Описание компенсации')
                            ->maxLength(65535)
                            ->nullable()
                            ->rows(2)
                            ->helperText('Обоснование компенсационной выплаты'),
                            
                        Forms\Components\TextInput::make('expenses_total')
                            ->label('Общие расходы')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('₽')
                            ->default(0),
                    ])->columns(2),
                        
                Forms\Components\Section::make('Справочная информация')
                    ->schema([
                        Forms\Components\Select::make('tax_status_id')
                            ->label('Налоговый статус')
                            ->relationship('taxStatus', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Выберите налоговый статус для расчета НДФЛ'),
                            
                        Forms\Components\Select::make('contract_type_id')
                            ->label('Тип договора')
                            ->relationship('contractType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\Select::make('specialty_id')
                            ->label('Специальность')
                            ->relationship('specialty', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Опционально - уточняющая специальность'),
                            
                        Forms\Components\Select::make('work_type_id')
                            ->label('Вид работ')
                            ->relationship('workType', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
                        
                Forms\Components\Section::make('Статус и даты')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => '📝 Черновик',
                                'pending_approval' => '⏳ Ожидает утверждения',
                                'approved' => '✅ Утвержден',
                                'paid' => '💰 Оплачен',
                            ])
                            ->required()
                            ->default('draft')
                            ->live()
                            ->helperText('Статус жизненного цикла отчета'),
                            
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Дата отправки')
                            ->nullable()
                            ->helperText('Автоматически заполняется при отправке на утверждение'),
                            
                        Forms\Components\DateTimePicker::make('approved_at')
                                ->label('Дата утверждения')
                                ->nullable()
                                ->helperText('Автоматически заполняется при утверждении'),
                            
                        Forms\Components\DateTimePicker::make('paid_at')
                                ->label('Дата оплаты')
                                ->nullable()
                                ->helperText('Автоматически заполняется при отметке "Оплачен"'),
                    ])->columns(2),
                        
                Forms\Components\Section::make('Расчетные поля (автоматические)')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Общая сумма')
                            ->numeric()
                            ->prefix('₽')
                            ->readOnly()
                            ->helperText('Базовая сумма + компенсации + расходы'),
                            
                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Сумма налога')
                            ->numeric()
                            ->prefix('₽')
                            ->readOnly()
                            ->helperText('Рассчитывается по налоговому статусу'),
                            
                        Forms\Components\TextInput::make('net_amount')
                            ->label('Чистая сумма')
                            ->numeric()
                            ->prefix('₽')
                            ->readOnly()
                            ->helperText('Общая сумма - налог'),
                    ])->columns(3)
                    ->hiddenOn('create')
                    ->description('Эти поля рассчитываются автоматически при сохранении'),
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
                    ->url(fn ($record) => $record->workRequest ? WorkRequestResource::getUrl('edit', [$record->workRequest->id]) : null)
                    ->openUrlInNewTab()
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('workers_count')
                    ->label('Работников')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn ($record) => $record->workers_count),
                    
                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Часы')
                    ->sortable()
                    ->alignRight()
                    ->suffix(' ч.'),
                    
                Tables\Columns\TextColumn::make('base_hourly_rate')
                    ->label('Ставка/час')
                    ->money('RUB')
                    ->sortable()
                    ->alignRight(),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Общая сумма')
                    ->money('RUB')
                    ->sortable()
                    ->alignRight()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('К выплате')
                    ->money('RUB')
                    ->sortable()
                    ->alignRight()
                    ->color('success')
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => '📝 Черновик',
                        'pending_approval' => '⏳ Ожидает утверждения',
                        'approved' => '✅ Утвержден',
                        'paid' => '💰 Оплачен',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_approval',
                        'success' => 'approved',
                        'green' => 'paid',
                    ])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Отправлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Утвержден')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Оплачен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'pending_approval' => 'Ожидает утверждения',
                        'approved' => 'Утвержден',
                        'paid' => 'Оплачен',
                    ]),
                    
                Tables\Filters\SelectFilter::make('tax_status_id')
                    ->label('Налоговый статус')
                    ->relationship('taxStatus', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('contract_type_id')
                    ->label('Тип договора')
                    ->relationship('contractType', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('specialty_id')
                    ->label('Специальность')
                    ->relationship('specialty', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('work_type_id')
                    ->label('Вид работ')
                    ->relationship('workType', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('created_at')
                    ->label('Дата создания')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('До'),
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
                    
                Tables\Filters\Filter::make('total_amount_range')
                    ->label('Сумма отчета')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('От суммы')
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('До суммы')
                            ->numeric()
                            ->prefix('₽'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('updateCalculations')
                    ->label('Пересчитать')
                    ->icon('heroicon-o-calculator')
                    ->color('gray')
                    ->action(fn (MassPersonnelReport $record) => $record->updateCalculations()),
                    
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Отправить на утверждение')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Отправка отчета на утверждение')
                    ->modalDescription('Вы уверены, что хотите отправить этот отчет на утверждение? После отправки статус изменится на "Ожидает утверждения".')
                    ->hidden(fn (MassPersonnelReport $record) => $record->status !== 'draft')
                    ->action(fn (MassPersonnelReport $record) => $record->submitForApproval()),
                    
                Tables\Actions\Action::make('approve')
                    ->label('Утвердить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Утверждение отчета')
                    ->modalDescription('Вы уверены, что хотите утвердить этот отчет? После утверждения статус изменится на "Утвержден".')
                    ->hidden(fn (MassPersonnelReport $record) => $record->status !== 'pending_approval')
                    ->action(fn (MassPersonnelReport $record) => $record->approve()),
                    
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Отметить как оплаченный')
                    ->icon('heroicon-o-banknotes')
                    ->color('green')
                    ->requiresConfirmation()
                    ->modalHeading('Отметка как оплаченный')
                    ->modalDescription('Вы уверены, что отчет оплачен? После подтверждения статус изменится на "Оплачен".')
                    ->hidden(fn (MassPersonnelReport $record) => $record->status !== 'approved')
                    ->action(fn (MassPersonnelReport $record) => $record->markAsPaid()),
                    
                Tables\Actions\Action::make('viewWorkers')
                    ->label('Работники')
                    ->icon('heroicon-o-user-group')
                    ->url(fn ($record) => self::getUrl('edit', [$record->id]) . '?activeRelationManager=0')
                    ->color('gray'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->emptyStateHeading('Нет отчетов')
            ->emptyStateDescription('Создайте первый отчет по массовому персоналу.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать отчет'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ContractorWorkersRelationManager::class,
            RelationManagers\VisitedLocationsRelationManager::class,
            RelationManagers\CompensationsRelationManager::class,
            RelationManagers\ExpensesRelationManager::class,
            RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMassPersonnelReports::route('/'),
            'create' => Pages\CreateMassPersonnelReport::route('/create'),
            'edit' => Pages\EditMassPersonnelReport::route('/{record}/edit'),
        ];
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'dispatcher', 'hr']);
    }
}
