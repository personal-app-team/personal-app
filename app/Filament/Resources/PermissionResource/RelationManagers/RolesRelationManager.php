<?php

namespace App\Filament\Resources\PermissionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';
    protected static ?string $title = 'Роли с этим разрешением';
    protected static ?string $label = 'роль';
    protected static ?string $pluralLabel = 'Роли';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Название роли'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название роли')
                    ->searchable()
                    ->sortable()
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
                    ),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Пользователей')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->label('Создана')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Добавить роль')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Выберите роль'),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Убрать роль'),
                    
                Tables\Actions\Action::make('view_role')
                    ->label('Перейти к роли')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => \App\Filament\Resources\RoleResource::getUrl('edit', [$record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
                    ->label('Убрать у выбранных'),
            ]);
    }
}
