<?php
// app/Filament/Resources/RoleResource.php - ОБНОВИ форму

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    
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
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание роли')
                            ->nullable()
                            ->maxLength(1000)
                            ->helperText('Краткое описание назначения роли'),
                    ]),
                    
                Forms\Components\Section::make('Разрешения')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->relationship('permissions', 'name')
                            ->searchable()
                            ->bulkToggleable()
                            ->label('Разрешения')
                            ->helperText('Выберите разрешения для этой роли')
                            ->gridDirection('row')
                            ->columns(2)
                            ->getOptionLabelFromRecordUsing(fn (Permission $record) => 
                                // Форматирование названия разрешения
                                $record->description ?: $record->name
                            ),
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
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->searchable()
                    ->toggleable()
                    ->tooltip(function ($state) {
                        return strlen($state) > 50 ? $state : null;
                    }),
                    
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Кол-во разрешений')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Пользователей')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                    
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
                    
                Tables\Filters\Filter::make('has_users')
                    ->label('Только с пользователями')
                    ->query(fn ($query) => $query->has('users')),
                    
                Tables\Filters\Filter::make('has_description')
                    ->label('Только с описанием')
                    ->query(fn ($query) => $query->whereNotNull('description')),
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
            ->emptyStateHeading('Нет ролей')
            ->emptyStateDescription('Создайте первую роль.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать роль'),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // Добавим позже RelationManager для пользователей
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
