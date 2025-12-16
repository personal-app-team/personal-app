<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractorResource\Pages;
use App\Models\Contractor;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;

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
                Tabs::make('Подрядчик')
                    ->tabs([
                        Tabs\Tab::make('Основная информация')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Реквизиты компании')
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
                                        
                                    ])->columns(2),

                                Section::make('Контактная информация')
                                    ->schema([
                                        Forms\Components\TextInput::make('contact_person')
                                            ->label('Контактное лицо (ФИО)')
                                            ->required()
                                            ->maxLength(255),
                                            
                                        Forms\Components\TextInput::make('contact_person_phone')
                                            ->label('Телефон контактного лица')
                                            ->tel()
                                            ->maxLength(20)
                                            ->nullable(),
                                            
                                        Forms\Components\TextInput::make('contact_person_email')
                                            ->label('Email контактного лица')
                                            ->email()
                                            ->maxLength(255)
                                            ->nullable(),
                                            
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Основной телефон компании')
                                            ->required()
                                            ->tel()
                                            ->maxLength(255),
                                            
                                        Forms\Components\TextInput::make('email')
                                            ->label('Основной email компании')
                                            ->required()
                                            ->email()
                                            ->maxLength(255),
                                    ])->columns(2),
                            ]),

                        // ... остальные вкладки без изменений
                        Tabs\Tab::make('Налоговая информация')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Договор и налоги')
                                    ->schema([
                                        Forms\Components\Select::make('contract_type_id')
                                            ->label('Тип договора')
                                            ->relationship('contractType', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->nullable()
                                            ->afterStateUpdated(function ($set, $state) {
                                                $set('tax_status_id', null);
                                            }),
                                            
                                        Forms\Components\Select::make('tax_status_id')
                                            ->label('Налоговый статус')
                                            ->relationship(
                                                name: 'taxStatus',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query, callable $get) => 
                                                    $query->where('contract_type_id', $get('contract_type_id'))
                                                        ->where('is_active', true)
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->visible(fn (callable $get): bool => (bool) $get('contract_type_id')),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Дополнительная информация')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Реквизиты')
                                    ->schema([
                                        Forms\Components\Textarea::make('address')
                                            ->label('Юридический адрес')
                                            ->rows(2)
                                            ->maxLength(65535)
                                            ->nullable(),
                                            
                                        Forms\Components\TextInput::make('inn')
                                            ->label('ИНН')
                                            ->maxLength(12)
                                            ->nullable(),
                                            
                                        Forms\Components\Textarea::make('bank_details')
                                            ->label('Банковские реквизиты')
                                            ->rows(3)
                                            ->maxLength(65535)
                                            ->nullable(),
                                    ]),

                                Section::make('Настройки')
                                    ->schema([
                                        Forms\Components\TagsInput::make('specializations')
                                            ->label('Специализации компании')
                                            ->placeholder('Введите специализацию')
                                            ->nullable()
                                            ->helperText('Общие специализации для быстрого поиска'),
                                            
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Заметки')
                                            ->rows(2)
                                            ->maxLength(65535)
                                            ->nullable(),
                                            
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Активный подрядчик')
                                            ->default(true),
                                    ]),
                            ]),

                        Tabs\Tab::make('Управление')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Доступ к системе')
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('User-представитель')
                                            ->relationship('user', 'email')
                                            ->searchable()
                                            ->preload()
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} ({$record->email})")
                                            ->helperText('Пользователь с ролью contractor_admin')
                                            ->nullable(),
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
                    
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Контактное лицо')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
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
                    
                Tables\Filters\Filter::make('has_user')
                    ->label('С представителем')
                    ->query(fn ($query) => $query->whereNotNull('user_id')),
                    
                Tables\Filters\Filter::make('has_rates')
                    ->label('Со ставками')
                    ->query(fn ($query) => $query->whereHas('contractorRates')),
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
                    
                Tables\Actions\Action::make('executors')
                    ->label('Исполнители')
                    ->icon('heroicon-o-users')
                    ->url(fn (Contractor $record) => UserResource::getUrl('index', [
                        'tableFilters[contractor][values]' => [$record->id]
                    ]))
                    ->color('gray')
                    ->badge(fn ($record) => $record->executors()->count())
                    ->badgeColor('gray'),
                    
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
            // Можно добавить RelationManager для ставок, но пока используем действие "Ставки"
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
