<?php

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
        // Получаем разрешения с группировкой
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy('group');
        
        // Создаем табы для каждой группы
        $tabs = [];
        
        foreach ($groupedPermissions as $group => $permissionsInGroup) {
            $groupName = $group ?: 'Общие';
            
            $tabs[] = Forms\Components\Tabs\Tab::make($groupName)
                ->badge($permissionsInGroup->count())
                ->badgeColor('gray')
                ->icon(function () use ($group) {
                    return match($group) {
                        'user' => 'heroicon-o-user',
                        'assignment' => 'heroicon-o-user-plus',
                        'work_request' => 'heroicon-o-document-text',
                        'shift' => 'heroicon-o-clock',
                        'expense' => 'heroicon-o-currency-dollar',
                        'compensation' => 'heroicon-o-banknotes',
                        'candidate' => 'heroicon-o-academic-cap',
                        'vacancy' => 'heroicon-o-briefcase',
                        'recruitment' => 'heroicon-o-user-group',
                        'department' => 'heroicon-o-building-office',
                        'contractor' => 'heroicon-o-building-office-2',
                        'project' => 'heroicon-o-folder',
                        'purpose' => 'heroicon-o-flag',
                        'address' => 'heroicon-o-map-pin',
                        'category' => 'heroicon-o-tag',
                        'specialty' => 'heroicon-o-wrench-screwdriver',
                        'photo' => 'heroicon-o-photo',
                        'activity' => 'heroicon-o-clipboard-document-list',
                        'system' => 'heroicon-o-cog-6-tooth',
                        'finance' => 'heroicon-o-currency-dollar',
                        'report' => 'heroicon-o-chart-bar',
                        default => 'heroicon-o-key',
                    };
                })
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->label('')
                        ->relationship('permissions', 'id')
                        ->searchable()
                        ->bulkToggleable()
                        ->gridDirection('row')
                        ->columns(2)
                        ->getOptionLabelFromRecordUsing(fn (Permission $record) => // ← ИСПРАВЛЕНО: getOptionLabelFromRecordUsing
                            $record->description ?: self::formatPermissionName($record->name)
                        ),
                ]);
        }
        
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->label('Название роли')
                            ->placeholder('Например: Администратор, Менеджер...')
                            ->columnSpan(1),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание роли')
                            ->nullable()
                            ->maxLength(1000)
                            ->helperText('Краткое описание назначения роли')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Разрешения')
                    ->description('Выберите разрешения для этой роли')
                    ->schema([
                        Forms\Components\Tabs::make('PermissionTabs')
                            ->tabs($tabs)
                            ->persistTabInQueryString()
                            ->contained(false),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * Форматирование названия разрешения для читаемости
     */
    private static function formatPermissionName(string $permissionName): string
    {
        // Разделяем по подчеркиваниям
        $parts = explode('_', $permissionName);
        
        // Убираем префиксы типа "view_any_", "create_", "update_", "delete_", "restore_", "force_delete_"
        $action = $parts[0];
        $model = implode(' ', array_slice($parts, 1));
        
        // Переводим действия на русский
        $actionTranslated = match($action) {
            'view' => 'Просмотр',
            'view_any' => 'Просмотр всех',
            'create' => 'Создание',
            'update' => 'Редактирование',
            'delete' => 'Удаление',
            'restore' => 'Восстановление',
            'force_delete' => 'Полное удаление',
            'manage' => 'Управление',
            'approve' => 'Утверждение',
            'export' => 'Экспорт',
            'import' => 'Импорт',
            default => ucfirst($action),
        };
        
        // Преобразуем название модели (например: "work_request" → "Work Request")
        $modelTranslated = str_replace('_', ' ', $model);
        $modelTranslated = ucwords($modelTranslated);
        
        return "{$actionTranslated} {$modelTranslated}";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название роли')
                    ->description(fn ($record) => $record->description ? substr($record->description, 0, 50) . '...' : ''),
                    
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Разрешений')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state == 0 => 'gray',
                        $state < 10 => 'success',
                        $state < 50 => 'primary',
                        default => 'warning',
                    }),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Пользователей')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state == 0 => 'gray',
                        $state == 1 => 'success',
                        $state < 5 => 'primary',
                        default => 'warning',
                    }),
                    
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
                    
                Tables\Filters\Filter::make('permissions_count')
                    ->label('Количество разрешений')
                    ->form([
                        Forms\Components\TextInput::make('min')
                            ->label('Минимум')
                            ->numeric()
                            ->placeholder('0'),
                        Forms\Components\TextInput::make('max')
                            ->label('Максимум')
                            ->numeric()
                            ->placeholder('100'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min'], fn ($query, $min) => $query->whereHas('permissions', fn ($q) => $q->havingRaw('COUNT(*) >= ?', [$min])))
                            ->when($data['max'], fn ($query, $max) => $query->whereHas('permissions', fn ($q) => $q->havingRaw('COUNT(*) <= ?', [$max])));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
                Tables\Actions\Action::make('duplicate')
                    ->label('Дублировать')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Role $record) {
                        $newRole = $record->replicate();
                        $newRole->name = $record->name . ' (копия)';
                        $newRole->save();
                        
                        // Копируем разрешения
                        $newRole->syncPermissions($record->permissions);
                    })
                    ->requiresConfirmation(),
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
            ->defaultSort('name', 'asc')
            ->selectCurrentPageOnly()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            // Можно добавить RelationManager для пользователей позже
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
