<?php
// app/Filament/Resources/PermissionResource.php - ОБНОВИ таблицу

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Filament\Resources\PermissionResource\RelationManagers;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;

class PermissionResource extends Resource
{
    protected static ?string $model = \Spatie\Permission\Models\Permission::class;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Разрешения';
    protected static ?int $navigationSort = 61;
    protected static ?string $modelLabel = 'разрешение';
    protected static ?string $pluralModelLabel = 'Разрешения';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Разрешения',
            'create' => 'Создать разрешение',
            'edit' => 'Редактировать разрешение',
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
                            ->label('Название разрешения')
                            ->placeholder('create_work_requests')
                            ->helperText('Используйте snake_case: create_work_requests'),
                        
                        Forms\Components\Select::make('group')
                            ->label('Группа/Модуль')
                            ->options(function () {
                                return Permission::query()
                                    ->select('group')
                                    ->whereNotNull('group')
                                    ->distinct()
                                    ->orderBy('group')
                                    ->pluck('group', 'group')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->default('system'),
                        
                        Forms\Components\TextInput::make('guard_name')
                            ->default('web')
                            ->required()
                            ->label('Guard Name')
                            ->helperText('Обычно "web" для веб-приложений'),
                    ])->columns(1),
                    
                Forms\Components\Section::make('Описание')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Описание на русском')
                            ->nullable()
                            ->maxLength(500)
                            ->helperText('Краткое описание для чего это разрешение'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Группа')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->color(fn ($state) => match($state) {
                        'work_request' => 'warning',
                        'user' => 'primary',
                        'financial' => 'success',
                        'system' => 'danger',
                        'project' => 'info',
                        'hr' => 'purple',
                        'shift' => 'orange',
                        'contractor' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Разрешение')
                    ->formatStateUsing(function ($state) {
                        // Простое форматирование без сложной логики
                        $parts = explode('_', $state);
                        if (count($parts) >= 2) {
                            $action = $parts[0];
                            $model = implode('_', array_slice($parts, 1));
                            
                            $actionMap = [
                                'view' => '👁️',
                                'create' => '➕',
                                'update' => '✏️',
                                'delete' => '🗑️',
                                'restore' => '♻️',
                                'force' => '💥',
                                'replicate' => '📋',
                                'manage' => '⚙️',
                                'approve' => '✅',
                                'access' => '🚪',
                                'export' => '📤',
                                'import' => '📥',
                            ];
                            
                            $actionIcon = $actionMap[$action] ?? '🔹';
                            return "{$actionIcon} {$state}";
                        }
                        return $state;
                    }),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(function ($state) {
                        return strlen($state) > 50 ? $state : null;
                    }),
                    
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Роли')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'admin' => '👑 Админ',
                            'initiator' => '📋 Инициатор',
                            'dispatcher' => '📞 Диспетчер',
                            'executor' => '👷 Исполнитель',
                            'contractor' => '🏢 Подрядчик',
                            'hr' => '👔 HR',
                            'manager' => '💼 Менеджер',
                            'trainee' => '👶 Стажер',
                            default => $state
                        };
                    })
                    ->colors([
                        'danger' => 'admin',
                        'success' => 'initiator',
                        'warning' => 'dispatcher',
                        'info' => 'executor',
                        'gray' => 'contractor',
                        'purple' => 'hr',
                        'orange' => 'manager',
                        'blue' => 'trainee',
                    ]),
                    
                Tables\Columns\TextColumn::make('direct_users_count')
                    ->label('Прямых')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->getStateUsing(function (Permission $record) {
                        return DB::table('model_has_permissions')
                            ->where('permission_id', $record->id)
                            ->where('model_type', User::class)
                            ->count();
                    })
                    ->tooltip('Пользователей с прямым назначением'),
                    
                Tables\Columns\TextColumn::make('users_via_roles_count')
                    ->label('Через роли')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->getStateUsing(function (Permission $record) {
                        return DB::table('role_has_permissions')
                            ->join('model_has_roles', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
                            ->where('role_has_permissions.permission_id', $record->id)
                            ->where('model_has_roles.model_type', User::class)
                            ->distinct('model_has_roles.model_id')
                            ->count('model_has_roles.model_id');
                    })
                    ->tooltip('Пользователей с разрешением через роли'),
                    
                Tables\Columns\TextColumn::make('total_users_count')
                    ->label('Всего')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->getStateUsing(function (Permission $record) {
                        $viaRoles = DB::table('role_has_permissions')
                            ->join('model_has_roles', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
                            ->where('role_has_permissions.permission_id', $record->id)
                            ->where('model_has_roles.model_type', User::class)
                            ->distinct('model_has_roles.model_id')
                            ->count('model_has_roles.model_id');
                            
                        $direct = DB::table('model_has_permissions')
                            ->where('permission_id', $record->id)
                            ->where('model_type', User::class)
                            ->count();
                            
                        return $viaRoles + $direct;
                    })
                    ->tooltip('Всего уникальных пользователей'),
                    
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color(fn ($state) => $state === 'web' ? 'success' : 'warning')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Создано'),
            ])
            ->filters([
                // Фильтр по группе (динамический из БД)
                SelectFilter::make('group')
                    ->label('Группа')
                    ->options(function () {
                        return Permission::query()
                            ->select('group')
                            ->whereNotNull('group')
                            ->distinct()
                            ->orderBy('group')
                            ->pluck('group', 'group')
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable(),
                
                // Фильтр по названию разрешения
                Filter::make('name')
                    ->label('Название разрешения')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Содержит текст')
                            ->placeholder('Например: view_any_'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['name'] ?? null,
                                fn ($query, $name) => $query->where('name', 'like', "%{$name}%")
                            );
                    }),
                
                // Фильтр по описанию
                Filter::make('description')
                    ->label('Описание')
                    ->form([
                        Forms\Components\TextInput::make('description')
                            ->label('Содержит текст')
                            ->placeholder('Например: Просмотр списка'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['description'] ?? null,
                                fn ($query, $description) => $query->where('description', 'like', "%{$description}%")
                            );
                    }),
                
                // Фильтр по наличию описания
                TernaryFilter::make('has_description')
                    ->label('Наличие описания')
                    ->nullable()
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('description'),
                        false: fn ($query) => $query->whereNull('description'),
                        blank: fn ($query) => $query,
                    ),
                
                // Фильтр по ролям
                SelectFilter::make('roles')
                    ->label('Роли')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->searchable(),
                
                // Фильтр по наличию ролей
                TernaryFilter::make('has_roles')
                    ->label('Назначено ролям')
                    ->nullable()
                    ->queries(
                        true: fn ($query) => $query->whereHas('roles'),
                        false: fn ($query) => $query->whereDoesntHave('roles'),
                        blank: fn ($query) => $query,
                    ),
                
                // Фильтр по guard_name
                SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ])
                    ->multiple(),
                
                // Фильтр по прямым пользователям
                TernaryFilter::make('has_direct_users')
                    ->label('Есть прямые пользователи')
                    ->nullable()
                    ->queries(
                        true: fn ($query) => $query->whereHas('users'),
                        false: fn ($query) => $query->whereDoesntHave('users'),
                        blank: fn ($query) => $query,
                    ),
                
                // Фильтр по дате создания
                Filter::make('created_at')
                    ->label('Дата создания')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('До'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
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
            ->emptyStateHeading('Нет разрешений')
            ->emptyStateDescription('Создайте первое разрешение.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать разрешение'),
            ])
            ->defaultSort('group', 'asc')
            ->deferFilters() // Для производительности
            ->persistFiltersInSession(); // Сохраняем фильтры в сессии
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
            RelationManagers\DirectUsersRelationManager::class,
            RelationManagers\UsersViaRolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
