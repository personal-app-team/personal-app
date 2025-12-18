<?php

namespace App\Filament\Resources\PermissionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\User;

class DirectUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Прямые назначения';
    protected static ?string $label = 'пользователь';
    protected static ?string $pluralLabel = 'Пользователи';

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

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Роли пользователя')
                    ->badge()
                    ->separator(', ')
                    ->limitList(2),
                    
                Tables\Columns\TextColumn::make('has_permission_via_roles')
                    ->label('Также через роли')
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(function (User $record) {
                        $permission = $this->getOwnerRecord();
                        $hasViaRoles = $record->roles()
                            ->whereHas('permissions', function ($query) use ($permission) {
                                $query->where('permissions.id', $permission->id);
                            })
                            ->exists();
                            
                        return $hasViaRoles ? 'ДА' : 'нет';
                    })
                    ->tooltip('Есть ли это разрешение через роли (в дополнение к прямому)'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->label('Назначено')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('only_direct')
                    ->label('Только прямые (без дублирования через роли)')
                    ->query(fn ($query) => 
                        $query->whereDoesntHave('roles.permissions', function ($q) {
                            $q->where('permissions.id', $this->getOwnerRecord()->id);
                        })
                    ),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Добавить прямое назначение')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Выберите пользователя')
                            ->getOptionLabelFromRecordUsing(fn (User $record) => 
                                $record->full_name . ' (' . $record->email . ')'
                            )
                            ->helperText(function () {
                                $permission = $this->getOwnerRecord();
                                $rolesWithPermission = \Spatie\Permission\Models\Role::whereHas('permissions', function ($q) use ($permission) {
                                    $q->where('permissions.id', $permission->id);
                                })->pluck('name')->toArray();
                                
                                if (empty($rolesWithPermission)) {
                                    return 'Внимание: ни одна роль не имеет этого разрешения';
                                }
                                
                                return 'Роли с этим разрешением: ' . implode(', ', $rolesWithPermission);
                            }),
                    ])
                    ->modalDescription('Вы добавляете прямое разрешение пользователю. Убедитесь, что это не дублирует разрешения через роли.'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Убрать прямое назначение')
                    ->modalDescription('Будет убрано только прямое назначение. Если пользователь имеет это разрешение через роль, оно останется.'),
                    
                Tables\Actions\Action::make('view_user')
                    ->label('Перейти к пользователю')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => \App\Filament\Resources\UserResource::getUrl('edit', [$record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
                    ->label('Убрать прямые назначения у выбранных'),
            ])
            ->emptyStateHeading('Нет прямых назначений пользователям')
            ->emptyStateDescription(function () {
                $permission = $this->getOwnerRecord();
                $rolesCount = $permission->roles()->count();
                $usersViaRoles = \DB::table('role_has_permissions')
                    ->join('model_has_roles', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
                    ->where('role_has_permissions.permission_id', $permission->id)
                    ->where('model_has_roles.model_type', User::class)
                    ->distinct('model_has_roles.model_id')
                    ->count('model_has_roles.model_id');
                
                return "Это разрешение есть у {$rolesCount} ролей и доступно {$usersViaRoles} пользователям через роли.";
            })
            ->emptyStateActions([
                Tables\Actions\AttachAction::make()
                    ->label('Добавить прямое назначение')
                    ->preloadRecordSelect(),
            ]);
    }
}
