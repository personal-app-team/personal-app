<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkTypeResource\Pages;
use App\Models\WorkType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkTypeResource extends Resource
{
    protected static ?string $model = WorkType::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Виды работ';
    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'вид работ';
    protected static ?string $pluralModelLabel = 'Виды работ';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о виде работ')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название вида работ')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: Покраска, Укладка плитки...'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(2)
                            ->maxLength(65535)
                            ->placeholder('Подробное описание вида работ...')
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный вид работ')
                            ->default(true)
                            ->helperText('Неактивные виды работ не будут показываться при выборе'),
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
                    ->sortable()
                    ->weight('medium'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(30)
                    ->searchable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('work_requests_count')
                    ->label('Заявок')
                    ->counts('workRequests')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные')
                    ->placeholder('Все виды работ')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
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
            ->emptyStateHeading('Нет видов работ')
            ->emptyStateDescription('Создайте первый вид работ.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать вид работ'),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkTypes::route('/'),
            'create' => Pages\CreateWorkType::route('/create'),
            'edit' => Pages\EditWorkType::route('/{record}/edit'),
        ];
    }
}
