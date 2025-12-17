<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractorResource\Pages;
use App\Filament\Resources\ContractorResource\RelationManagers;
use App\Models\Contractor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractorResource extends Resource
{
    protected static ?string $model = Contractor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = '👥 Управление персоналом';
    protected static ?string $navigationLabel = 'Подрядчики';
    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'подрядчик';
    protected static ?string $pluralModelLabel = 'Подрядчики';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Подрядчик')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Реквизиты компании')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make('Основные реквизиты')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Название компании')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('ООО "Стройка"'),
                                            
                                        Forms\Components\TextInput::make('contractor_code')
                                            ->label('Код подрядчика')
                                            ->maxLength(10)
                                            ->disabled()
                                            ->helperText('Генерируется автоматически'),
                                            
                                        Forms\Components\TextInput::make('inn')
                                            ->label('ИНН')
                                            ->maxLength(12)
                                            ->nullable()
                                            ->helperText('12 цифр'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Адрес и банковские реквизиты')
                                    ->schema([
                                        Forms\Components\Textarea::make('address')
                                            ->label('Юридический адрес')
                                            ->rows(2)
                                            ->nullable()
                                            ->columnSpanFull(),
                                            
                                        Forms\Components\Textarea::make('bank_details')
                                            ->label('Банковские реквизиты')
                                            ->rows(3)
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Контактная информация')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\Section::make('Руководитель компании')
                                    ->schema([
                                        Forms\Components\TextInput::make('director')
                                            ->label('ФИО руководителя')
                                            ->maxLength(255)
                                            ->nullable(),
                                            
                                        Forms\Components\TextInput::make('director_phone')
                                            ->label('Телефон руководителя')
                                            ->tel()
                                            ->maxLength(20)
                                            ->nullable(),
                                            
                                        Forms\Components\TextInput::make('director_email')
                                            ->label('Email руководителя')
                                            ->email()
                                            ->maxLength(255)
                                            ->nullable(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Контакты компании')
                                    ->schema([
                                        Forms\Components\TextInput::make('company_phone')
                                            ->label('Основной телефон компании')
                                            ->tel()
                                            ->maxLength(20)
                                            ->nullable(),
                                            
                                        Forms\Components\TextInput::make('company_email')
                                            ->label('Основной email компании')
                                            ->email()
                                            ->maxLength(255)
                                            ->nullable(),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Налоги и договор')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\Section::make('Договорные отношения')
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
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Дополнительно')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Настройки')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Активный подрядчик')
                                            ->default(true)
                                            ->inline(false),
                                            
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Заметки')
                                            ->rows(3)
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название компании')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->contractor_code ? "Код: {$record->contractor_code}" : null),
                    
                Tables\Columns\TextColumn::make('director')
                    ->label('Руководитель')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('company_phone')
                    ->label('Телефон компании')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('company_email')
                    ->label('Email компании')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contractType.name')
                    ->label('Тип договора')
                    ->badge()
                    ->color('primary')
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('taxStatus.name')
                    ->label('Налоговый статус')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} (" . ($record->taxStatus?->tax_rate * 100) . "%)" : '—')
                    ->color('success')
                    ->toggleable()
                    ->placeholder('—'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные')
                    ->placeholder('Все подрядчики')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
                    
                Tables\Filters\SelectFilter::make('contract_type_id')
                    ->label('Тип договора')
                    ->relationship('contractType', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('tax_status_id')
                    ->label('Налоговый статус')
                    ->relationship('taxStatus', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('rates')
                    ->label('Ставки')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn (Contractor $record) => ContractorRateResource::getUrl('index', [
                        'tableFilters[contractor][values]' => [$record->id]
                    ]))
                    ->color('success')
                    ->badge(fn ($record) => $record->contractorRates()->count())
                    ->badgeColor('success'),
                    
                Tables\Actions\Action::make('work_requests')
                    ->label('Заявки')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Contractor $record) => WorkRequestResource::getUrl('index', [
                        'tableFilters[contractor_id][values]' => [$record->id]
                    ]))
                    ->color('info')
                    ->badge(fn ($record) => $record->workRequests()->count())
                    ->badgeColor('info'),
                    
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->emptyStateHeading('Нет подрядчиков')
            ->emptyStateDescription('Создайте первого подрядчика.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать подрядчика'),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ContractorRatesRelationManager::class,
            RelationManagers\WorkRequestsRelationManager::class,
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContractors::route('/'),
            'create' => Pages\CreateContractor::route('/create'),
            'edit' => Pages\EditContractor::route('/{record}/edit'),
        ];
    }
}
