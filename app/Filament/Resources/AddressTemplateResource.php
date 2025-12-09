<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddressTemplateResource\Pages;
use App\Models\AddressTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AddressTemplateResource extends Resource
{
    protected static ?string $model = AddressTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Шаблоны адресов';
    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'шаблон адреса';
    protected static ?string $pluralModelLabel = 'Шаблоны адресов';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Шаблон адреса')
                    ->schema([
                        Forms\Components\Textarea::make('full_address')
                            ->label('Полный адрес')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('location_type')
                            ->label('Тип локации')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: офис, склад, магазин'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный шаблон')
                            ->default(true)
                            ->helperText('Неактивные шаблоны не будут показываться при выборе'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('location_type')
                    ->label('Тип локации')
                    ->searchable()
                    ->sortable(),
                
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
                    ->placeholder('Все шаблоны')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
                    
                Tables\Filters\SelectFilter::make('location_type')
                    ->label('Тип локации')
                    ->options(fn () => AddressTemplate::distinct()->pluck('location_type', 'location_type'))
                    ->searchable(),
            ])
            ->actions([
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
            ->emptyStateHeading('Нет шаблонов адресов')
            ->emptyStateDescription('Создайте первый шаблон адреса.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать шаблон'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddressTemplates::route('/'),
            'create' => Pages\CreateAddressTemplate::route('/create'),
            'edit' => Pages\EditAddressTemplate::route('/{record}/edit'),
        ];
    }
}
