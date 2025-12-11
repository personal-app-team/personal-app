<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Адреса проекта';

    protected static ?string $label = 'адрес';
    
    protected static ?string $pluralLabel = 'Адреса';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название адреса')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Например: Парк Горького, Центральный вход'),
                
                Forms\Components\Textarea::make('full_address')
                    ->label('Полный адрес')
                    ->required()
                    ->rows(2)
                    ->placeholder('г. Москва, ул. Крымский Вал, 9'),
                
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(2)
                    ->columnSpanFull()
                    ->placeholder('Дополнительная информация об адресе...'),
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
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('full_address')
                    ->label('Адрес')
                    ->limit(40),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Проектов')
                    ->counts('projects'),
                
                Tables\Columns\TextColumn::make('work_requests_count')
                    ->label('Заявок')
                    ->counts('workRequests'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать новый адрес'),
                    
                // В headerActions() улучшим AttachAction:
                Tables\Actions\AttachAction::make()
                    ->label('Добавить существующий адрес')
                    ->recordSelect(
                        fn (Tables\Actions\AttachAction $action) => $action->getRecordSelect()
                            ->preload()
                            ->searchable(['name', 'full_address'])
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Address::where('name', 'like', "%{$search}%")
                                    ->orWhere('full_address', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('full_address', 'id')
                                    ->map(function ($address, $id) {
                                        $addressRecord = \App\Models\Address::find($id);
                                        return "{$addressRecord->name} - {$address}";
                                    });
                            })
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
                    ->label('Открепить от проекта'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить полностью'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Открепить выбранные'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }
}
