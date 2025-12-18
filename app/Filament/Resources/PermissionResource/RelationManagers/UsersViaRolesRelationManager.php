<?php

namespace App\Filament\Resources\PermissionResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UsersViaRolesRelationManager extends RelationManager
{
    protected static ?string $title = 'Пользователи через роли';
    protected static ?string $label = 'пользователь';
    protected static ?string $pluralLabel = 'Пользователи';
    
    // Кастомная связь - не используем стандартную
    protected static string $relationship = 'roles';
    
    // Переопределяем запрос для получения пользователей
    public function getTableQuery(): Builder
    {
        $permission = $this->getOwnerRecord();
        
        // Получаем пользователей, у которых есть разрешение через роль
        return User::whereHas('roles.permissions', function ($query) use ($permission) {
            $query->where('permissions.id', $permission->id);
        });
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Имя'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->getStateUsing(fn (User $record) => $record->full_name)
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('surname', 'like', "%{$search}%")
                              ->orWhere('patronymic', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('surname', $direction)
                                     ->orderBy('name', $direction)
                                     ->orderBy('patronymic', $direction);
                    }),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles_with_permission')
                    ->label('Роли с этим разрешением')
                    ->badge()
                    ->separator(', ')
                    ->color('warning')
                    ->getStateUsing(function (User $record) {
                        $permission = $this->getOwnerRecord();
                        
                        return $record->roles()
                            ->whereHas('permissions', function ($query) use ($permission) {
                                $query->where('permissions.id', $permission->id);
                            })
                            ->pluck('name')
                            ->map(function ($roleName) {
                                return match($roleName) {
                                    'admin' => '👑 Админ',
                                    'initiator' => '📋 Инициатор',
                                    'dispatcher' => '📞 Диспетчер',
                                    'executor' => '👷 Исполнитель',
                                    'contractor' => '🏢 Подрядчик',
                                    'hr' => '👔 HR',
                                    'manager' => '💼 Менеджер',
                                    default => $roleName
                                };
                            })
                            ->toArray();
                    }),

                Tables\Columns\TextColumn::make('has_direct_permission')
                    ->label('Прямое назначение')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(function (User $record) {
                        $permission = $this->getOwnerRecord();
                        return $record->hasDirectPermission($permission->name) ? 'ДА' : 'нет';
                    })
                    ->tooltip('Есть ли прямое назначение в дополнение к ролевому'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->label('В системе с')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('only_via_roles')
                    ->label('Только через роли (без прямых)')
                    ->query(fn (Builder $query) => 
                        $query->whereDoesntHave('permissions', function ($q) {
                            $q->where('permissions.id', $this->getOwnerRecord()->id);
                        })
                    ),
                    
                Tables\Filters\Filter::make('with_direct_permission')
                    ->label('И с прямым назначением')
                    ->query(fn (Builder $query) => 
                        $query->whereHas('permissions', function ($q) {
                            $q->where('permissions.id', $this->getOwnerRecord()->id);
                        })
                    ),
            ])
            ->headerActions([
                // Нет действий - нельзя добавить пользователя через эту вкладку
                // Управление через роли
            ])
            ->actions([
                Tables\Actions\Action::make('view_user')
                    ->label('Перейти к пользователю')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (User $record) => \App\Filament\Resources\UserResource::getUrl('edit', [$record->id]))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('manage_roles')
                    ->label('Управление ролями')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url(fn (User $record) => \App\Filament\Resources\UserResource::getUrl('edit', [$record->id]) . '?activeRelationManager=0')
                    ->tooltip('Изменить роли пользователя')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // Нет массовых действий
            ])
            ->emptyStateHeading('Нет пользователей с этим разрешением через роли')
            ->emptyStateDescription('Назначьте это разрешение роли, чтобы пользователи получили его через роли.')
            ->emptyStateActions([
                Tables\Actions\Action::make('assign_to_role')
                    ->label('Назначить разрешение роли')
                    ->icon('heroicon-o-key')
                    ->url(fn () => \App\Filament\Resources\RoleResource::getUrl('index'))
                    ->openUrlInNewTab(),
            ]);
    }
    
    // Отключаем проверку стандартной связи
    public static function canViewForRecord($ownerRecord, $pageClass): bool
    {
        return true;
    }
}
