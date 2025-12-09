<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoResource\Pages;
use App\Filament\Resources\PhotoResource\RelationManagers;
use App\Models\Photo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PhotoResource extends Resource
{
    protected static ?string $model = Photo::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Фотографии';
    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'фотография';
    protected static ?string $pluralModelLabel = 'Фотографии';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Фотографии',
            'create' => 'Создать фотографию',
            'edit' => 'Редактировать фотографию',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('photoable_type')
                            ->label('Тип объекта')
                            ->options([
                                'App\\Models\\Shift' => '💰 Смена',
                                'App\\Models\\VisitedLocation' => '📍 Посещенная локация',
                                'App\\Models\\MassPersonnelReport' => '👥 Отчет по массовому персоналу',
                                'App\\Models\\Expense' => '🧾 Расход',
                                'App\\Models\\ContractorWorker' => '👷 Работник подрядчика',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('photoable_id', null)),
                            
                        Forms\Components\Select::make('photoable_id')
                            ->label('Объект')
                            ->searchable()
                            ->preload()
                            ->options(function (callable $get) {
                                $type = $get('photoable_type');
                                
                                if (!$type) {
                                    return [];
                                }
                                
                                return match ($type) {
                                    'App\\Models\\Shift' => \App\Models\Shift::query()
                                        ->with(['user', 'workRequest'])
                                        ->get()
                                        ->mapWithKeys(fn ($shift) => [
                                            $shift->id => "Смена #{$shift->id} - " . ($shift->user?->full_name ?? 'Неизвестно')
                                        ]),
                                    'App\\Models\\VisitedLocation' => \App\Models\VisitedLocation::query()
                                        ->with(['visitable'])
                                        ->get()
                                        ->mapWithKeys(fn ($location) => [
                                            $location->id => "Локация #{$location->id} - " . ($location->address ? Str::limit($location->address, 30) : 'Без адреса')
                                        ]),
                                    'App\\Models\\MassPersonnelReport' => \App\Models\MassPersonnelReport::query()
                                        ->with(['workRequest'])
                                        ->get()
                                        ->mapWithKeys(fn ($report) => [
                                            $report->id => "Отчет #{$report->id}" . ($report->workRequest ? " - Заявка #{$report->workRequest->id}" : '')
                                        ]),
                                    'App\\Models\\Expense' => \App\Models\Expense::query()
                                        ->with(['expensable'])
                                        ->get()
                                        ->mapWithKeys(fn ($expense) => [
                                            $expense->id => "Расход #{$expense->id} - " . ($expense->type_display ?? 'Без типа')
                                        ]),
                                    'App\\Models\\ContractorWorker' => \App\Models\ContractorWorker::query()
                                        ->with(['massPersonnelReport'])
                                        ->get()
                                        ->mapWithKeys(fn ($worker) => [
                                            $worker->id => "Работник #{$worker->id} - " . ($worker->full_name ?? 'Без имени')
                                        ]),
                                    default => [],
                                };
                            })
                            ->required(),
                            
                        Forms\Components\Select::make('photo_type')
                            ->label('Тип фотографии')
                            ->options(Photo::getPhotoTypeOptions())
                            ->required()
                            ->default(Photo::TYPE_OTHER),
                            
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Фотография')
                            ->image()
                            ->directory('photos')
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->preserveFilenames()
                            ->imagePreviewHeight('250')
                            ->loadingIndicatorPosition('left')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left')
                            ->helperText('Максимальный размер: 10MB'),
                            
                        Forms\Components\TextInput::make('file_name')
                            ->label('Название файла')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => 'photo_' . now()->format('Y-m-d_H-i-s')),
                            
                        Forms\Components\TextInput::make('original_name')
                            ->label('Оригинальное название')
                            ->maxLength(255)
                            ->nullable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Метаданные')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->maxLength(65535)
                            ->nullable()
                            ->columnSpanFull()
                            ->rows(2),
                            
                        Forms\Components\DateTimePicker::make('taken_at')
                            ->label('Время съемки')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\TextInput::make('latitude')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.000001)
                            ->nullable(),
                            
                        Forms\Components\TextInput::make('longitude')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.000001)
                            ->nullable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Верификация')
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицировано')
                            ->default(false),
                            
                        Forms\Components\Select::make('verified_by_id')
                            ->label('Верифицировано пользователем')
                            ->relationship('verifiedBy', 'full_name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->disabled(fn ($get) => !$get('is_verified')),
                            
                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Дата верификации')
                            ->nullable()
                            ->disabled(fn ($get) => !$get('is_verified')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Фото')
                    ->size(60)
                    ->square()
                    ->extraImgAttributes(['class' => 'rounded-lg']),
                    
                Tables\Columns\TextColumn::make('photo_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => (new Photo())->getPhotoTypeDisplay())
                    ->colors([
                        'success' => Photo::TYPE_SHIFT,
                        'warning' => Photo::TYPE_LOCATION,
                        'info' => Photo::TYPE_EXPENSE,
                        'gray' => Photo::TYPE_MASS_REPORT,
                        'purple' => Photo::TYPE_WORKER,
                        'dark' => Photo::TYPE_OTHER,
                    ]),
                    
                Tables\Columns\TextColumn::make('photoable_type')
                    ->label('Объект')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'App\\Models\\Shift' => '💰 Смена',
                        'App\\Models\\VisitedLocation' => '📍 Локация',
                        'App\\Models\\MassPersonnelReport' => '👥 Отчет',
                        'App\\Models\\Expense' => '🧾 Расход',
                        'App\\Models\\ContractorWorker' => '👷 Работник',
                        default => class_basename($state),
                    })
                    ->colors([
                        'success' => 'App\\Models\\Shift',
                        'warning' => 'App\\Models\\VisitedLocation',
                        'info' => 'App\\Models\\MassPersonnelReport',
                        'gray' => 'App\\Models\\Expense',
                        'purple' => 'App\\Models\\ContractorWorker',
                    ]),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Вериф.')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('verifiedBy.full_name')
                    ->label('Верифицировал')
                    ->placeholder('—')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('taken_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Размер')
                    ->formatStateUsing(fn ($state) => $state ? round($state / 1024, 2) . ' KB' : '0 KB')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Добавлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('photo_type')
                    ->label('Тип фотографии')
                    ->options(Photo::getPhotoTypeOptions())
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('photoable_type')
                    ->label('Тип объекта')
                    ->options([
                        'App\\Models\\Shift' => '💰 Смена',
                        'App\\Models\\VisitedLocation' => '📍 Локация',
                        'App\\Models\\MassPersonnelReport' => '👥 Отчет',
                        'App\\Models\\Expense' => '🧾 Расход',
                        'App\\Models\\ContractorWorker' => '👷 Работник',
                    ])
                    ->multiple(),
                    
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Верификация')
                    ->placeholder('Все')
                    ->trueLabel('Только верифицированные')
                    ->falseLabel('Только неверифицированные'),
                    
                Tables\Filters\Filter::make('has_coordinates')
                    ->label('📍 Есть координаты')
                    ->query(fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude')),
                    
                Tables\Filters\Filter::make('taken_at')
                    ->label('Дата съемки')
                    ->form([
                        Forms\Components\DatePicker::make('taken_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('taken_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['taken_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('taken_at', '>=', $date),
                            )
                            ->when(
                                $data['taken_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('taken_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('view_full')
                    ->label('Просмотр')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn ($record) => "
                        <div style='text-align: center; padding: 20px;'>
                            <img src='{$record->url}' style='max-width: 100%; max-height: 70vh; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);' alt='{$record->file_name}'>
                            <div style='margin-top: 20px; text-align: left;'>
                                <p><strong>Название:</strong> {$record->file_name}</p>
                                <p><strong>Тип:</strong> {$record->getPhotoTypeDisplay()}</p>
                                <p><strong>Описание:</strong> " . ($record->description ?? '—') . "</p>
                                <p><strong>Размер:</strong> " . round($record->file_size / 1024, 2) . " KB</p>
                                <p><strong>Создано:</strong> " . $record->taken_at->format('d.m.Y H:i') . "</p>
                            </div>
                        </div>
                    ")
                    ->modalHeading('Просмотр фотографии')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть'),
                    
                Tables\Actions\Action::make('verify')
                    ->label('Верифицировать')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($record) => $record->verify(auth()->user()))
                    ->hidden(fn ($record) => $record->is_verified)
                    ->requiresConfirmation(),
                    
                Tables\Actions\Action::make('unverify')
                    ->label('Снять верификацию')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn ($record) => $record->unverify())
                    ->visible(fn ($record) => $record->is_verified)
                    ->requiresConfirmation(),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                        
                    Tables\Actions\BulkAction::make('verify')
                        ->label('Верифицировать выбранные')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->verify(auth()->user());
                        })
                        ->requiresConfirmation(),
                        
                    Tables\Actions\BulkAction::make('unverify')
                        ->label('Снять верификацию')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->unverify();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('Нет фотографий')
            ->emptyStateDescription('Загрузите первую фотографию.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Загрузить фотографию'),
            ])
            ->defaultSort('taken_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhotos::route('/'),
            'create' => Pages\CreatePhoto::route('/create'),
            'edit' => Pages\EditPhoto::route('/{record}/edit'),
        ];
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'dispatcher', 'executor', 'hr']);
    }
}
