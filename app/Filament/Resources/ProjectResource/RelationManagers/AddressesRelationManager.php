<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rule;

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
                        // ИСПРАВЛЕННЫЙ ВАРИАНТ: используем Rule::unique с явным where
                        function ($get) {
                            return Rule::unique('addresses', 'full_address')
                                ->ignore($this->getRecord()?->id)
                                ->where(function ($query) {
                                    return $query->where('is_template', false);
                                });
                        }
                    ]),
                
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
                    
                // Кнопка 2: Выбрать из шаблона
                Tables\Actions\Action::make('createFromTemplate')
                    ->label('Выбрать из шаблона')
                    ->form([
                        Forms\Components\Select::make('template_id')
                            ->label('Шаблон адреса')
                            ->options(
                                Address::templates()->pluck('full_address', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                Address::templates()
                                    ->where('full_address', 'like', "%{$search}%")
                                    ->orWhere('short_name', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('full_address', 'id')
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                Address::templates()->find($value)?->full_address
                            ),
                    ])
                    ->action(function (array $data): void {
                        $template = Address::templates()->find($data['template_id']);
                        
                        if (!$template) {
                            Notification::make()
                                ->title('Шаблон не найден')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Создаем новый адрес на основе шаблона
                        $address = Address::create([
                            'short_name' => $template->short_name,
                            'full_address' => $template->full_address,
                            'location_type' => $template->location_type,
                            'is_template' => false,  // Это НЕ шаблон
                        ]);
                        
                        // Привязываем к проекту
                        $this->getOwnerRecord()->addresses()->attach($address->id);
                        
                        Notification::make()
                            ->title('Адрес создан из шаблона')
                            ->success()
                            ->send();
                    })
                    ->modalWidth('xl'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
                    ->label('Открепить от проекта'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить полностью'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Открепить выбранные'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }
}
