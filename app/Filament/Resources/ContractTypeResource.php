<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractTypeResource\Pages;
use App\Models\ContractType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractTypeResource extends Resource
{
    protected static ?string $model = ContractType::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Типы договоров';
    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'тип договора';
    protected static ?string $pluralModelLabel = 'Типы договоров';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Самозанятый, ГПХ, ИП...'),
                            
                        Forms\Components\TextInput::make('code')
                            ->label('Код')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('self_employed, gph, ip...'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Описание типа договора...')
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный')
                            ->default(true)
                            ->helperText('Неактивные типы не будут доступны для выбора'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->searchable()
                    ->placeholder('—'),
                    
                Tables\Columns\TextColumn::make('tax_statuses_count')
                    ->label('Налоговых статусов')
                    ->counts('taxStatuses')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('employment_histories_count')
                    ->label('Записей в истории')
                    ->counts('employmentHistories')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
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
                    ->placeholder('Все типы')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('tax_statuses')
                    ->label('Налоговые статусы')
                    ->icon('heroicon-o-calculator')
                    ->url(fn (ContractType $record) => TaxStatusResource::getUrl('index', [
                        'tableFilters[contract_type][values]' => [$record->id]
                    ]))
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
            ->emptyStateHeading('Нет типов договоров')
            ->emptyStateDescription('Создайте первый тип договора.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать тип договора'),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\TaxStatusesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContractTypes::route('/'),
            'create' => Pages\CreateContractType::route('/create'),
            'edit' => Pages\EditContractType::route('/{record}/edit'),
        ];
    }
}
