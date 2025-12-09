<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurposeTemplateResource\Pages;
use App\Models\PurposeTemplate;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rule;

class PurposeTemplateResource extends Resource
{
    protected static ?string $model = PurposeTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    
    protected static ?string $navigationLabel = 'Шаблоны назначений';
    
    protected static ?string $modelLabel = 'шаблон назначения';
    
    protected static ?string $pluralModelLabel = 'Шаблоны назначений';
    
    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название назначения')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Например: Монтаж, Демонтаж, Уход за растениями')
                            ->validationMessages([
                                'unique' => 'Шаблон с таким названием уже существует',
                            ]),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный шаблон')
                            ->default(true)
                            ->helperText('Неактивные шаблоны не будут показываться при выборе'),
                    ]),
                // УБИРАЕМ настройки оплаты - они будут в Purpose
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
                    ->wrap() // ПЕРЕНОС ТЕКСТА
                    ->weight('medium'), // ЖИРНЫЙ ШРИФТ
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные шаблоны'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                
                Tables\Actions\Action::make('createPurpose')
                    ->label('Добавить в проект')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('project_id')
                            ->label('Проект')
                            ->options(Project::where('status', 'active')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->placeholder('Выберите проект'),
                    ])
                    ->action(function (PurposeTemplate $record, array $data) {
                        $project = Project::find($data['project_id']);
                        
                        // Проверяем дублирование
                        $existingPurpose = \App\Models\Purpose::where('project_id', $project->id)
                            ->where('name', $record->name)
                            ->first();
                            
                        if ($existingPurpose) {
                            Notification::make()
                                ->title('Ошибка')
                                ->body("Назначение '{$record->name}' уже существует в проекте '{$project->name}'")
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Создаем назначение из шаблона
                        $purpose = $record->createPurposeForProject($project);
                        
                        Notification::make()
                            ->title('Назначение создано')
                            ->body("Шаблон '{$record->name}' успешно добавлен в проект '{$project->name}'")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->emptyStateHeading('Нет шаблонов назначений')
            ->emptyStateDescription('Создайте первый шаблон назначения.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать шаблон')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurposeTemplates::route('/'),
            'create' => Pages\CreatePurposeTemplate::route('/create'),
            'edit' => Pages\EditPurposeTemplate::route('/{record}/edit'),
        ];
    }

    // Русские названия для страниц
    public static function getPageLabels(): array
    {
        return [
            'index' => 'Шаблоны назначений',
            'create' => 'Создать шаблон',
            'edit' => 'Редактировать шаблон',
        ];
    }
}
