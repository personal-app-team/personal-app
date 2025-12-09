<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationGroup = '🏗️ Проекты и геолокации';
    
    protected static ?string $navigationLabel = 'Проекты';
    
    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'проект';
    protected static ?string $pluralModelLabel = 'Проекты';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Проекты',
            'create' => 'Создать проект',
            'edit' => 'Редактировать проект',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название проекта')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required(),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата окончания')
                            ->required()
                            ->afterOrEqual('start_date'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус проекта')
                            ->options([
                                'planned' => 'Запланирован',
                                'active' => 'Активный', 
                                'completed' => 'Завершен',
                                'cancelled' => 'Отменен',
                            ])
                            ->required()
                            ->default('planned'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Начало')
                    ->date('d.m.Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Окончание')
                    ->date('d.m.Y')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'planned',
                        'success' => 'active',
                        'gray' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Запланирован',
                        'active' => 'Активный',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен',
                    }),
                
                Tables\Columns\TextColumn::make('addresses_count')
                    ->label('Адресов')
                    ->counts('addresses'),
                
                Tables\Columns\TextColumn::make('purposes_count')
                    ->label('Назначений')
                    ->counts('purposes'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'planned' => 'Запланирован',
                        'active' => 'Активный',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                Tables\Actions\ViewAction::make()
                    ->label('Просмотреть'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\RelationManagers\AddressesRelationManager::class,
            \App\Filament\Resources\RelationManagers\PurposesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    // Добавить в класс ProjectResource
    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('edit_database') || 
            auth()->user()->hasPermissionTo('view_projects');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermissionTo('edit_database');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermissionTo('edit_database');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermissionTo('edit_database');
    }

    // ДОБАВЬ ЭТОТ МЕТОД ДЛЯ РУССКИХ НАЗВАНИЙ ДЕЙСТВИЙ
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    // ДОБАВЬ ЭТОТ МЕТОД ДЛЯ ПЕРЕВОДА CREATE ACTION
    public static function getCreateAction(): \Filament\Actions\Action
    {
        return parent::getCreateAction()
            ->label('Новый проект');
    }
}
