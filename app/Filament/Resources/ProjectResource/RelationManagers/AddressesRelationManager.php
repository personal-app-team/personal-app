<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Rules\UniqueNormalizedAddress;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Адреса проекта';

    protected static ?string $label = 'адрес';
    
    protected static ?string $pluralLabel = 'Адреса';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('short_name')
                    ->label('Короткое название адреса')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Например: Парк Горького, Центральный вход'),
                
                Forms\Components\Textarea::make('full_address')
                    ->label('Полный адрес')
                    ->required()
                    ->rows(2)
                    ->placeholder('г. Москва, ул. Крымский Вал, 9')
                    ->rules([
                        'required',
                        // ИСПРАВЛЕННЫЙ ВАРИАНТ с нашим правилом:
                        function ($get, $state, $context) {
                            $project = $this->getOwnerRecord();
                            $record = $context === 'edit' ? $this->getRecord() : null;
                            
                            return new UniqueNormalizedAddress(
                                $record?->id,     // ID для исключения
                                false,            // Только не шаблоны
                                $project->id      // Ограничение по проекту
                            );
                        }
                    ])
                    ->helperText('Адрес будет автоматически нормализован для предотвращения дублирования.'),
                
                Forms\Components\Textarea::make('location_type')
                    ->label('Тип локации')
                    ->rows(2)
                    ->columnSpanFull()
                    ->placeholder('Например: Традиционные локации'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('short_name')
            ->columns([
                Tables\Columns\TextColumn::make('short_name')
                    ->label('Короткое название')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('full_address')
                    ->label('Полный адрес')
                    ->limit(40),
                
                Tables\Columns\TextColumn::make('location_type')
                    ->label('Тип локации')
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_template')
                    ->label('Шаблон')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Проектов')
                    ->counts('projects'),
                
                Tables\Columns\TextColumn::make('work_requests_count')
                    ->label('Заявок')
                    ->counts('workRequests'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать новый адрес')
                    ->mutateFormDataUsing(function (array $data): array {
                        // При создании через RelationManager - это всегда не шаблон
                        $data['is_template'] = false;
                        return $data;
                    }),
                    
                // Кнопка: Выбрать из шаблона
                Tables\Actions\Action::make('addFromTemplates')
                    ->label('Добавить из шаблонов')
                    ->modalHeading('Выберите шаблоны адресов')
                    ->modalSubmitActionLabel('Добавить адреса в проект')
                    ->modalCancelActionLabel('Отмена')
                    ->modalWidth('3xl')
                    ->form([
                        Forms\Components\CheckboxList::make('template_ids')
                            ->label('')
                            ->options(function () {
                                $project = $this->getOwnerRecord();
                                
                                // Используем наш rule для нормализации
                                $rule = new UniqueNormalizedAddress(null, false, $project->id);
                                
                                // Получаем все шаблоны
                                $templates = Address::templates()
                                    ->orderBy('full_address')
                                    ->get();
                                
                                // Фильтруем шаблоны, которые уже есть в проекте
                                $availableTemplates = $templates->filter(function ($template) use ($rule, $project) {
                                    try {
                                        // Проверяем с помощью нашего правила
                                        $rule->validate(
                                            'full_address',
                                            $template->full_address,
                                            function ($message) {
                                                // Если валидация не проходит, значит адрес уже существует
                                                throw new \Exception($message);
                                            }
                                        );
                                        return true;
                                    } catch (\Exception $e) {
                                        return false;
                                    }
                                });
                                
                                return $availableTemplates->pluck('full_address', 'id');
                            })
                            ->columns(1)
                            ->required()
                            ->bulkToggleable(),
                    ])
                    ->action(function (array $data) {
                        $project = $this->getOwnerRecord();
                        $added = 0;
                        $errors = [];
                        
                        foreach ($data['template_ids'] as $templateId) {
                            $template = Address::templates()->find($templateId);
                            
                            if (!$template) {
                                $errors[] = "Шаблон с ID {$templateId} не найден";
                                continue;
                            }
                            
                            try {
                                // Проверяем с помощью нашего правила
                                $rule = new UniqueNormalizedAddress(null, false, $project->id);
                                $rule->validate('full_address', $template->full_address, function ($message) {
                                    throw new \Exception($message);
                                });
                                
                                // Создаем адрес
                                $address = Address::create([
                                    'short_name' => $template->short_name,
                                    'full_address' => $template->full_address,
                                    'location_type' => $template->location_type,
                                    'is_template' => false,
                                ]);
                                
                                $project->addresses()->attach($address->id);
                                $added++;
                                
                            } catch (\Exception $e) {
                                $errors[] = "Адрес '{$template->full_address}': " . $e->getMessage();
                            }
                        }
                        
                        if ($added > 0) {
                            Notification::make()
                                ->title('Адреса добавлены')
                                ->body("Добавлено адресов: {$added}" . 
                                       (!empty($errors) ? "\nОшибки: " . count($errors) : ''))
                                ->success()
                                ->send();
                        }
                        
                        if (!empty($errors)) {
                            Notification::make()
                                ->title('Частично выполнено')
                                ->body(implode("\n", array_slice($errors, 0, 3)) . 
                                       (count($errors) > 3 ? "\n... и еще " . (count($errors) - 3) . " ошибок" : ''))
                                ->warning()
                                ->send();
                        }
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // УБИРАЕМ "Удалить полностью" - оставляем только "Открепить от проекта"
                Tables\Actions\DetachAction::make()
                    ->label('Открепить от проекта')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Открепить адрес от проекта')
                    ->modalDescription('Вы уверены, что хотите открепить этот адрес от проекта? Адрес останется в системе и может быть использован в других проектах.')
                    ->modalSubmitActionLabel('Да, открепить')
                    ->modalCancelActionLabel('Отмена'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Открепить выбранные')
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}
