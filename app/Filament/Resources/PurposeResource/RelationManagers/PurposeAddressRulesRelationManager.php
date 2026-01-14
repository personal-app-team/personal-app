<?php

namespace App\Filament\Resources\PurposeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurposeAddressRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'addressRules';

    protected static ?string $title = 'Правила по адресам';

    protected static ?string $label = 'правило по адресу';
    
    protected static ?string $pluralLabel = 'Правила по адресам';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('project_id')
                    ->default(fn () => $this->getOwnerRecord()->project_id),
                
                Forms\Components\Select::make('address_id')
                    ->label('Адрес')
                    ->options(fn () => $this->getOwnerRecord()->project->addresses->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->helperText('Оставьте пустым для общего правила')
                    ->nullable(),
                
                Forms\Components\TextInput::make('payer_company')
                    ->label('Компания-плательщик')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('ЦЕХ, БС, ЦФ, УС и т.д.'),
                
                Forms\Components\TextInput::make('priority')
                    ->label('Приоритет')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(10)
                    ->helperText('1 - высший приоритет'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payer_company')
            ->columns([
                Tables\Columns\TextColumn::make('address.name')
                    ->label('Адрес')
                    ->formatStateUsing(fn ($state) => $state ?: 'Общее правило'),
                
                Tables\Columns\TextColumn::make('payer_company')
                    ->label('Компания-плательщик')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('priority')
                    ->label('Приоритет')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить правило'),
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
            ->defaultSort('priority', 'asc');
    }

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->payer_selection_type === 'address_based';
    }
}
