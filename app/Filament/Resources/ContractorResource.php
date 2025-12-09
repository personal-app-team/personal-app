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
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название компании')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('ООО "Стройка"'),
                            
                        Forms\Components\Select::make('user_id')
                            ->label('User-представитель')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} ({$record->email})")
                            ->helperText('Пользователь с ролью contractor, который будет управлять компанией через портал')
                            ->nullable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Контактная информация')
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Контактное лицо (ФИО)')
                            ->maxLength(255)
                            ->placeholder('Иванов Иван Иванович'),
                            
                        Forms\Components\TextInput::make('contact_person_phone')
                            ->label('Телефон контактного лица')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+7 (999) 123-45-67'),
                            
                        Forms\Components\TextInput::make('contact_person_email')
                            ->label('Email контактного лица')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('ivanov@example.com'),
                            
                        Forms\Components\TextInput::make('phone')
                            ->label('Основной телефон компании')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+7 (495) 123-45-67'),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('Основной email компании')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('info@company.ru'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Реквизиты')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Юридический адрес')
                            ->rows(2)
                            ->maxLength(65535)
                            ->placeholder('г. Москва, ул. Примерная, д. 1'),
                            
                        Forms\Components\TextInput::make('inn')
                            ->label('ИНН')
                            ->maxLength(12)
                            ->placeholder('1234567890'),
                            
                        Forms\Components\Textarea::make('bank_details')
                            ->label('Банковские реквизиты')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Банк: ПАО "Сбербанк"\nРасчетный счет: 40702810123456789012\nКорр. счет: 30101234567890123456\nБИК: 044525225'),
                    ])->columns(1),

                // НОВАЯ СЕКЦИЯ ДЛЯ НАЛОГОВОЙ СИСТЕМЫ
                Forms\Components\Section::make('Налоговая информация')
                    ->schema([
                        Forms\Components\Select::make('contract_type_id')
                            ->label('Тип договора компании')
                            ->relationship('contractType', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                // Сбрасываем налоговый статус при смене типа договора
                                $set('tax_status_id', null);
                            })
                            ->helperText('Организационно-правовая форма компании'),

                        Forms\Components\Select::make('tax_status_id')
                            ->label('Налоговый статус компании')
                            ->relationship(
                                name: 'taxStatus',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query, callable $get) => 
                                    $query->where('contract_type_id', $get('contract_type_id'))
                                          ->where('is_active', true)
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Основной налоговый режим компании')
                            ->visible(fn (callable $get): bool => (bool) $get('contract_type_id')),
                    ])->columns(2),   
                    
                Forms\Components\Section::make('Специализации и настройки')
                    ->schema([
                        Forms\Components\TagsInput::make('specializations')
                            ->label('Специализации компании')
                            ->placeholder('Введите специализацию и нажмите Enter')
                            ->helperText('Специальности, по которым подрядчик предоставляет персонал'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(2)
                            ->maxLength(65535)
                            ->placeholder('Дополнительная информация о подрядчике...'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный подрядчик')
                            ->default(true)
                            ->helperText('Неактивные подрядчики не будут показываться при выборе'),
                    ])->columns(1),
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
                    ->description(fn ($record) => $record->contact_person),
                    
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Представитель')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Не назначен'),
                    
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
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('taxStatus.name')
                    ->label('Налоговый статус')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} (" . ($record->taxStatus?->tax_rate * 100) . "%)" : '—')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable()
                    ->placeholder('—'),    
                    
                Tables\Columns\TextColumn::make('executors_count')
                    ->label('Исполнителей')
                    ->counts('executors')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state : 'нет'),
                    
                Tables\Columns\TextColumn::make('specializations')
                    ->label('Специализации')
                    ->badge()
                    ->separator(',')
                    ->limitList(2)
                    ->toggleable(),
                    
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
                    
                Tables\Filters\Filter::make('has_executors')
                    ->label('С исполнителями')
                    ->query(fn ($query) => $query->has('executors')),
                    
                Tables\Filters\Filter::make('has_user')
                    ->label('С представителем')
                    ->query(fn ($query) => $query->whereNotNull('user_id')),
                    
                Tables\Filters\Filter::make('no_user')
                    ->label('Без представителя')
                    ->query(fn ($query) => $query->whereNull('user_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('view_executors')
                    ->label('Исполнители')
                    ->icon('heroicon-o-users')
                    ->url(fn (Contractor $record) => UserResource::getUrl('index', [
                        'tableFilters[contractor][values]' => [$record->id]
                    ]))
                    ->color('gray')
                    ->hidden(fn ($record) => $record->executors()->count() === 0),
                    
                Tables\Actions\Action::make('statistics')
                    ->label('Статистика')
                    ->icon('heroicon-o-chart-bar')
                    ->modalHeading(fn ($record) => "Статистика: {$record->name}")
                    ->modalContent(fn ($record) => view('filament.resources.contractor-resource.statistics', [
                        'contractor' => $record
                    ]))
                    ->modalCancelActionLabel('Закрыть'),
                    
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
            // Можно добавить RelationManager для исполнителей
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
