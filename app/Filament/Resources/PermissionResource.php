<?php

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
                            ->options([
                                'activity_log' => '📊 Логи активности',
                                'address' => '📍 Адреса',
                                'assignment' => '📋 Назначения',
                                'candidate' => '👤 Кандидаты',
                                'category' => '🗂️ Категории',
                                'compensation' => '💰 Компенсации',
                                'contractor' => '🏢 Подрядчики',
                                'department' => '🏛️ Отделы',
                                'employment_history' => '📝 История трудоустройства',
                                'expense' => '🧾 Расходы',
                                'hiring_decision' => '✅ Решения о найме',
                                'initiator_grant' => '🔑 Права инициатора',
                                'interview' => '🗣️ Собеседования',
                                'mass_personnel_report' => '👥 Массовый персонал',
                                'permission' => '🔐 Разрешения',
                                'photo' => '📷 Фотографии',
                                'position_change_request' => '🔄 Запросы на изменение должности',
                                'project' => '📁 Проекты',
                                'purpose' => '🎯 Назначения работ',
                                'recruitment_request' => '🔍 Заявки на подбор',
                                'role' => '👥 Роли',
                                'shift' => '⏰ Смены',
                                'specialty' => '🎓 Специальности',
                                'tax_status' => '💰 Налоговые статусы',
                                'trainee_request' => '👶 Заявки на стажировку',
                                'user' => '👤 Пользователи',
                                'vacancy' => '📋 Вакансии',
                                'visited_location' => '📍 Посещенные локации',
                                'work_request' => '📝 Заявки на работы',
                                'work_type' => '🔧 Виды работ',
                                'system' => '⚙️ Системные',
                                'financial' => '💳 Финансы',
                            ])
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
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Создано'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Группа')
                    ->options([
                        'work_request' => 'Заявки на работы',
                        'user' => 'Пользователи',
                        'project' => 'Проекты',
                        'financial' => 'Финансы',
                        'system' => 'Системные',
                        'hr' => 'Кадры (HR)',
                        'assignment' => 'Назначения',
                        'contractor' => 'Подрядчики',
                        'shift' => 'Смены',
                        'address' => 'Адреса',
                        'category' => 'Категории',
                        'specialty' => 'Специальности',
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('has_description')
                    ->label('Только с описанием')
                    ->query(fn ($query) => $query->whereNotNull('description')),
                    
                Tables\Filters\Filter::make('has_roles')
                    ->label('Только с ролями')
                    ->query(fn ($query) => $query->has('roles')),
                    
                Tables\Filters\Filter::make('has_direct_users')
                    ->label('С прямыми пользователями')
                    ->query(fn ($query) => 
                        $query->whereHas('users')
                    ),
                    
                Tables\Filters\Filter::make('has_users_via_roles')
                    ->label('С пользователями через роли')
                    ->query(fn ($query) => 
                        $query->whereHas('roles.users')
                    ),
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
            ->defaultSort('group', 'asc');
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
