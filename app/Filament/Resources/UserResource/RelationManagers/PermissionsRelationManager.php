<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';
    protected static ?string $title = 'Индивидуальные разрешения';
    protected static ?string $label = 'разрешение';
    protected static ?string $pluralLabel = 'Разрешения';
    
    // Показываем только на странице редактирования
    public static function canViewForRecord($ownerRecord, $pageClass): bool
    {
        return $pageClass === \App\Filament\Resources\UserResource\Pages\EditUser::class;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('name')
                    ->label('Разрешение')
                    ->options(function () {
                        return Permission::all()->mapWithKeys(function ($permission) {
                            // Добавляем русские описания
                            $description = match($permission->name) {
                                'create_work_requests' => '📋 Создание заявок',
                                'view_work_requests' => '👁️ Просмотр заявок',
                                'edit_work_requests' => '✏️ Редактирование заявок',
                                'delete_work_requests' => '🗑️ Удаление заявок',
                                'manage_users' => '👥 Управление пользователями',
                                'manage_roles' => '🔑 Управление ролями',
                                'manage_permissions' => '🔐 Управление разрешениями',
                                default => $permission->name
                            };
                            
                            return [$permission->name => $description];
                        })->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
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
                    )
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->getStateUsing(fn ($record) => $record->description ?? '-'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->label('Добавлено')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Добавить разрешение')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Выберите разрешение')
                            ->options(function () {
                                return Permission::all()->mapWithKeys(function ($permission) {
                                    $description = match($permission->name) {
                                        'create_work_requests' => '📋 Создание заявок',
                                        'view_work_requests' => '👁️ Просмотр заявок',
                                        'edit_work_requests' => '✏️ Редактирование заявок',
                                        'delete_work_requests' => '🗑️ Удаление заявок',
                                        'manage_users' => '👥 Управление пользователями',
                                        'manage_roles' => '🔑 Управление ролями',
                                        'manage_permissions' => '🔐 Управление разрешениями',
                                        default => $permission->name
                                    };
                                    
                                    return [$permission->id => $description];
                                })->toArray();
                            })
                            ->searchable(),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Удалить разрешение'),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
                    ->label('Удалить выбранные разрешения'),
            ])
            ->emptyStateHeading('Нет индивидуальных разрешений')
            ->emptyStateDescription('Добавьте индивидуальные разрешения для этого пользователя.')
            ->emptyStateActions([
                Tables\Actions\AttachAction::make()
                    ->label('Добавить разрешение')
                    ->preloadRecordSelect(),
            ]);
    }
}
