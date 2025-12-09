<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Категории специалистов';
    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'категория';
    protected static ?string $pluralModelLabel = 'Категории';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название категории')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: Садовники, Строители...'),
                            
                        Forms\Components\TextInput::make('prefix')
                            ->label('Префикс для номера заявки')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Например: ST, SA, UB...')
                            ->helperText('Уникальный код (2-3 символа) для генерации номеров заявок'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Описание категории...')
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активная категория')
                            ->default(true)
                            ->helperText('Неактивные категории не будут показываться при выборе'),
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
                    
                Tables\Columns\TextColumn::make('prefix')
                    ->label('Префикс')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => strtoupper($state)),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->searchable()
                    ->placeholder('—'),
                    
                Tables\Columns\TextColumn::make('specialties_count')
                    ->label('Специальностей')
                    ->counts('specialties')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('work_requests_count')
                    ->label('Заявок')
                    ->counts('workRequests')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные')
                    ->placeholder('Все категории')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('view_specialties')
                    ->label('Специальности')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (Category $record) => SpecialtyResource::getUrl('index', [
                        'tableFilters[category][values]' => [$record->id]
                    ]))
                    ->color('gray'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->emptyStateHeading('Нет категорий')
            ->emptyStateDescription('Создайте первую категорию.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать категорию'),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\SpecialtiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
