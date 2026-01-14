<?php

namespace App\Filament\Resources\PurposeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurposePayerCompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'payerCompanies';

    protected static ?string $title = 'Варианты оплаты';

    protected static ?string $label = 'вариант оплаты';
    
    protected static ?string $pluralLabel = 'Варианты оплаты';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('project_id')
                    ->default(fn () => $this->getOwnerRecord()->project_id),
                
                Forms\Components\TextInput::make('payer_company')
                    ->label('Компания-плательщик')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('ЦЕХ, БС, ЦФ, УС и т.д.'),
                
                Forms\Components\Textarea::make('description')
                    ->label('Описание варианта')
                    ->rows(2)
                    ->columnSpanFull(),
                
                Forms\Components\TextInput::make('order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payer_company')
            ->columns([
                Tables\Columns\TextColumn::make('payer_company')
                    ->label('Компания-плательщик')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить вариант оплаты'),
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
            ->defaultSort('order', 'asc');
    }

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        return in_array($ownerRecord->payer_selection_type, ['optional', 'address_based']);
    }
}
