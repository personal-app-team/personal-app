<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\PayerSelectionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurposesRelationManager extends RelationManager
{
    protected static string $relationship = 'purposes';

    protected static ?string $title = 'Назначения проекта';

    protected static ?string $label = 'назначение';
    
    protected static ?string $pluralLabel = 'Назначения';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название назначения')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(3)
                    ->columnSpanFull(),
                
                // НОВЫЕ ПОЛЯ
                Forms\Components\Select::make('payer_selection_type')
                    ->label('Тип выбора плательщика')
                    ->options([
                        'strict' => 'Строгая привязка',
                        'optional' => 'Опциональный выбор', 
                        'address_based' => 'Зависит от адреса',
                    ])
                    ->default('strict')
                    ->required()
                    ->live(),
                
                Forms\Components\TextInput::make('default_payer_company')
                    ->label('Компания-плательщик по умолчанию')
                    ->maxLength(255)
                    ->placeholder('ЦЕХ, БС, ЦФ, УС и т.д.')
                    ->hidden(fn (Forms\Get $get) => $get('payer_selection_type') === 'optional'),
                
                Forms\Components\Toggle::make('has_custom_payer_selection')
                    ->label('Ручной выбор плательщика')
                    ->helperText('Если включено, можно будет выбирать компанию при создании заявки')
                    ->default(false),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Активно')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium'),
                
                // НОВЫЕ КОЛОНКИ
                Tables\Columns\TextColumn::make('payer_selection_type')
                    ->label('Тип оплаты')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state?->value ?? $state) {
                        'strict' => 'Строгая',
                        'optional' => 'Выбор', 
                        'address_based' => 'По адресу',
                        default => $state?->value ?? $state,
                    })
                    ->color(fn ($state) => match($state?->value ?? $state) {
                        'strict' => 'success',
                        'optional' => 'warning',
                        'address_based' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('default_payer_company')
                    ->label('Плательщик')
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50),
                
                Tables\Columns\IconColumn::make('has_custom_payer_selection')
                    ->label('Ручной выбор')
                    ->boolean()
                    ->trueIcon('heroicon-o-hand-raised')
                    ->falseIcon('heroicon-o-cog')
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                // СТАТИСТИКА
                Tables\Columns\TextColumn::make('payer_companies_count')
                    ->label('Вариантов оплаты')
                    ->counts('payerCompanies'),
                
                Tables\Columns\TextColumn::make('address_rules_count')
                    ->label('Правил по адресам')
                    ->counts('addressRules'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payer_selection_type')
                    ->label('Тип оплаты')
                    ->options([
                        'strict' => 'Строгая привязка',
                        'optional' => 'Опциональный выбор',
                        'address_based' => 'По адресу',
                    ]),
                
                Tables\Filters\TernaryFilter::make('has_custom_payer_selection')
                    ->label('Ручной выбор плательщика'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить назначение'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('managePayerCompanies')
                    ->label('Варианты оплаты')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn ($record) => \App\Filament\Resources\PurposeResource::getUrl('edit', ['record' => $record])),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
