<?php

namespace App\Filament\Resources\ExpenseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Фотографии чеков';
    protected static ?string $label = 'фотография чека';
    protected static ?string $pluralLabel = 'Фотографии чеков';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('Фотография чека')
                    ->image()
                    ->directory('expense-receipts')
                    ->maxSize(10240)
                    ->required()
                    ->preserveFilenames()
                    ->imagePreviewHeight('250'),
                    
                Forms\Components\TextInput::make('file_name')
                    ->label('Название файла')
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => 'receipt_' . now()->format('Y-m-d_H-i-s')),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->maxLength(65535)
                    ->nullable(),
                    
                Forms\Components\DateTimePicker::make('taken_at')
                    ->label('Время съемки')
                    ->required()
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Чек')
                    ->size(80)
                    ->square(),
                    
                Tables\Columns\TextColumn::make('file_name')
                    ->label('Название')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Вериф.')
                    ->boolean()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('taken_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Верификация')
                    ->placeholder('Все')
                    ->trueLabel('Только верифицированные')
                    ->falseLabel('Только неверифицированные'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить фото чека'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('view_full')
                    ->label('Просмотр')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn ($record) => "
                        <div style='text-align: center;'>
                            <img src='{$record->url}' style='max-width: 100%; max-height: 70vh; border-radius: 8px;' alt='{$record->file_name}'>
                        </div>
                    ")
                    ->modalHeading('Просмотр чека')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }
}
