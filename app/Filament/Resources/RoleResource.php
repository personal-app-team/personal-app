<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    
    // ДОБАВЛЯЕМ РУССКИЕ LABELS И ГРУППУ
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Роли';
    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'роль';
    protected static ?string $pluralModelLabel = 'Роли';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Роли',
            'create' => 'Создать роль',
            'edit' => 'Редактировать роль',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->label('Название роли')
                            ->placeholder('Например: Администратор, Менеджер...'),
                    ]),
                    
                Forms\Components\Section::make('Разрешения')
                    ->schema([
                        Forms\Components\Select::make('permissions')
                            ->relationship('permissions', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Разрешения')
                            ->helperText('Выберите разрешения для этой роли'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название роли'),
                    
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Кол-во разрешений')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Создана'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Обновлена'),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_permissions')
                    ->label('Только с разрешениями')
                    ->query(fn ($query) => $query->has('permissions')),
            ])
            // ОБНОВЛЯЕМ ACTIONS С РУССКИМИ НАЗВАНИЯМИ
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            // ОБНОВЛЯЕМ BULK ACTIONS
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
