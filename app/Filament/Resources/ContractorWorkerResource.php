<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractorWorkerResource\Pages;
use App\Filament\Resources\ContractorWorkerResource\RelationManagers;
use App\Models\ContractorWorker;
use App\Models\MassPersonnelReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractorWorkerResource extends Resource
{
    protected static ?string $model = ContractorWorker::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Работники массового персонала';
    protected static ?string $modelLabel = 'работник массового персонала';
    protected static ?string $pluralModelLabel = 'Работники массового персонала';
    protected static ?string $navigationGroup = '👥 Управление персоналом';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('mass_personnel_report_id')
                            ->label('Отчет массового персонала')
                            ->relationship(
                                name: 'massPersonnelReport',
                                titleAttribute: 'id',
                                modifyQueryUsing: fn (Builder $query) => 
                                    $query->with('workRequest')->orderBy('id', 'desc')
                            )
                            ->getOptionLabelFromRecordUsing(fn (MassPersonnelReport $record) => 
                                "Отчет #{$record->id} (Заявка: " . ($record->workRequest ? $record->workRequest->request_number : 'Н/Д') . ")"
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                            
                        Forms\Components\TextInput::make('full_name')
                            ->label('ФИО работника')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Примечания')
                            ->rows(3)
                            ->maxLength(65535)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Подтверждение диспетчера')
                    ->schema([
                        Forms\Components\Toggle::make('is_confirmed')
                            ->label('Подтвержден диспетчером')
                            ->live()
                            ->onColor('success')
                            ->offColor('danger'),
                            
                        Forms\Components\Select::make('confirmed_by')
                            ->label('Подтвердил')
                            ->relationship('confirmator', 'full_name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('is_confirmed'))
                            ->required(fn (callable $get) => $get('is_confirmed')),
                            
                        Forms\Components\DateTimePicker::make('confirmed_at')
                            ->label('Дата подтверждения')
                            ->default(now())
                            ->visible(fn (callable $get) => $get('is_confirmed'))
                            ->required(fn (callable $get) => $get('is_confirmed')),
                            
                        Forms\Components\TextInput::make('photo_missing_reason')
                            ->label('Причина отсутствия фото')
                            ->maxLength(255)
                            ->visible(fn (callable $get) => $get('is_confirmed'))
                            ->helperText('Укажите причину, если нет фото для подтверждения работы'),
                            
                        Forms\Components\TextInput::make('calculated_hours')
                            ->label('Рассчитанные часы')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.5)
                            ->suffix('ч.')
                            ->helperText('Часы с округлением до 30 минут (0.5 ч)')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('massPersonnelReport.id')
                    ->label('Отчет')
                    ->sortable()
                    ->searchable()
                    ->url(fn (ContractorWorker $record) => 
                        MassPersonnelReportResource::getUrl('edit', [$record->mass_personnel_report_id])
                    )
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn ($state) => "Отчет #{$state}"),
                    
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
                    
                Tables\Columns\TextColumn::make('confirmator.full_name')
                    ->label('Подтвердил')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('Дата подтверждения')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
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
                Tables\Filters\SelectFilter::make('mass_personnel_report_id')
                    ->label('Отчет массового персонала')
                    ->relationship('massPersonnelReport', 'id')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (MassPersonnelReport $record) => 
                        "Отчет #{$record->id}"
                    ),
                    
                Tables\Filters\Filter::make('is_confirmed')
                    ->label('Только подтвержденные')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('is_confirmed', true)),
                    
                Tables\Filters\Filter::make('not_confirmed')
                    ->label('Только неподтвержденные')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('is_confirmed', false)),
                    
                Tables\Filters\Filter::make('with_missing_photo')
                    ->label('С отсутствующим фото')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNotNull('photo_missing_reason')),
                    
                Tables\Filters\Filter::make('calculated_hours')
                    ->label('Часы работы')
                    ->form([
                        Forms\Components\TextInput::make('min_hours')
                            ->label('От')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.5),
                        Forms\Components\TextInput::make('max_hours')
                            ->label('До')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.5),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_hours'],
                                fn (Builder $query, $hours): Builder => $query->where('calculated_hours', '>=', $hours),
                            )
                            ->when(
                                $data['max_hours'],
                                fn (Builder $query, $hours): Builder => $query->where('calculated_hours', '<=', $hours),
                            );
                    }),
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
                    
                Tables\Actions\Action::make('recalculate')
                    ->label('Пересчитать часы')
                    ->icon('heroicon-o-calculator')
                    ->color('gray')
                    ->action(fn (ContractorWorker $record) => $record->calculateHours()),
                    
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
            ])
            ->emptyStateHeading('Нет работников массового персонала')
            ->emptyStateDescription('Добавьте первого работника через отчет массового персонала.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить работника'),
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
            'index' => Pages\ListContractorWorkers::route('/'),
            'create' => Pages\CreateContractorWorker::route('/create'),
            'edit' => Pages\EditContractorWorker::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['massPersonnelReport', 'confirmator'])
            ->orderBy('created_at', 'desc');
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'dispatcher', 'hr']);
    }
}
