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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Разрешение')
                    ->formatStateUsing(fn ($state) => 
                        match($state) {
                            'create_work_requests' => '📋 Создание заявок',
                            'view_work_requests' => '👁️ Просмотр заявок',
                            'edit_work_requests' => '✏️ Редактирование заявок',
                            'delete_work_requests' => '🗑️ Удаление заявок',
                            'manage_users' => '👥 Управление пользователями',
                            'manage_roles' => '🔑 Управление ролями',
                            'manage_permissions' => '🔐 Управление разрешениями',
                            default => $state
                        }
                    ),
                    
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
                    ->formatStateUsing(fn ($state) => 
                        match($state) {
                            'admin' => '👑 Админ',
                            'initiator' => '📋 Инициатор',
                            'dispatcher' => '📞 Диспетчер',
                            'executor' => '👷 Исполнитель',
                            'contractor' => '🏢 Подрядчик',
                            'hr' => '👔 HR',
                            'manager' => '💼 Менеджер',
                            default => $state
                        }
                    )
                    ->colors([
                        'danger' => 'admin',
                        'success' => 'initiator',
                        'warning' => 'dispatcher',
                        'info' => 'executor',
                        'gray' => 'contractor',
                        'purple' => 'hr',
                        'orange' => 'manager',
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
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
            RelationManagers\DirectUsersRelationManager::class,  // Прямые назначения
            RelationManagers\UsersViaRolesRelationManager::class, // Через роли
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
