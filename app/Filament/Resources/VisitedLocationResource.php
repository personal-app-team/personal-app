<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitedLocationResource\Pages;
use App\Filament\Resources\VisitedLocationResource\RelationManagers;
use App\Models\VisitedLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class VisitedLocationResource extends Resource
{
    protected static ?string $model = VisitedLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Геолокации и фото';
    protected static ?string $navigationLabel = 'Посещенные локации';
    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'посещенная локация';
    protected static ?string $pluralModelLabel = 'Посещенные локации';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Посещенные локации',
            'create' => 'Создать посещенную локацию',
            'edit' => 'Редактировать посещенную локацию',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('visitable_type')
                            ->label('Тип объекта')
                            ->options([
                                'App\\Models\\Shift' => '💰 Смена',
                                'App\\Models\\MassPersonnelReport' => '👥 Отчет по массовому персоналу',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('visitable_id', null)),
                            
                        Forms\Components\Select::make('visitable_id')
                            ->label('Объект')
                            ->searchable()
                            ->preload()
                            ->options(function (callable $get) {
                                $type = $get('visitable_type');
                                
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
                                    'App\\Models\\MassPersonnelReport' => \App\Models\MassPersonnelReport::query()
                                        ->with(['workRequest'])
                                        ->get()
                                        ->mapWithKeys(fn ($report) => [
                                            $report->id => "Отчет #{$report->id}" . ($report->workRequest ? " - Заявка #{$report->workRequest->id}" : '')
                                        ]),
                                    default => [],
                                };
                            })
                            ->required(),
                            
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->helperText('Полный адрес посещенной локации'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Геолокация')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.000001)
                            ->nullable()
                            ->helperText('Например: 55.7558'),
                            
                        Forms\Components\TextInput::make('longitude')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.000001)
                            ->nullable()
                            ->helperText('Например: 37.6173'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Время посещения')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Начало посещения')
                            ->required()
                            ->default(now())
                            ->helperText('Когда началось посещение локации'),
                            
                        Forms\Components\DateTimePicker::make('ended_at')
                            ->label('Конец посещения')
                            ->required()
                            ->default(now()->addHour())
                            ->helperText('Когда закончилось посещение локации'),
                            
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Продолжительность (минуты)')
                            ->numeric()
                            ->readOnly()
                            ->helperText('Рассчитывается автоматически')
                            ->suffix('мин.'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Дополнительная информация')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->maxLength(65535)
                            ->nullable()
                            ->columnSpanFull()
                            ->rows(3)
                            ->helperText('Дополнительные заметки о посещении'),
                            
                        Forms\Components\TextInput::make('workers_count')
                            ->label('Количество работников')
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->helperText('Только для массового персонала')
                            ->visible(fn (callable $get): bool => 
                                $get('visitable_type') === 'App\\Models\\MassPersonnelReport'
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('visitable_type')
                    ->label('Тип объекта')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'App\\Models\\Shift' => '💰 Смена',
                        'App\\Models\\MassPersonnelReport' => '👥 Отчет масс. перс.',
                        default => class_basename($state),
                    })
                    ->colors([
                        'success' => 'App\\Models\\Shift',
                        'warning' => 'App\\Models\\MassPersonnelReport',
                    ]),
                    
                Tables\Columns\TextColumn::make('visitable_id')
                    ->label('Объект')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record->visitable) {
                            return '#' . $state;
                        }
                        
                        return match (get_class($record->visitable)) {
                            'App\\Models\\Shift' => 'Смена #' . $state,
                            'App\\Models\\MassPersonnelReport' => 'Отчет #' . $state,
                            default => '#' . $state,
                        };
                    })
                    ->url(function ($record) {
                        if (!$record->visitable) {
                            return null;
                        }
                        
                        return match (get_class($record->visitable)) {
                            'App\\Models\\Shift' => \App\Filament\Resources\ShiftResource::getUrl('edit', [$record->visitable_id]),
                            'App\\Models\\MassPersonnelReport' => \App\Filament\Resources\MassPersonnelReportResource::getUrl('edit', [$record->visitable_id]),
                            default => null,
                        };
                    })
                    ->openUrlInNewTab(),
                    
                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->address;
                    }),
                    
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Длительность')
                    ->formatStateUsing(fn ($state) => $state . ' мин.')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Конец')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('workers_count')
                    ->label('Работников')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->visible(fn ($record) => $record->visitable_type === 'App\\Models\\MassPersonnelReport'),
                    
                Tables\Columns\TextColumn::make('photos_count')
                    ->label('Фото')
                    ->counts('photos')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visitable_type')
                    ->label('Тип объекта')
                    ->options([
                        'App\\Models\\Shift' => '💰 Смена',
                        'App\\Models\\MassPersonnelReport' => '👥 Отчет по массовому персоналу',
                    ]),
                    
                Tables\Filters\Filter::make('has_photos')
                    ->label('📷 Есть фото')
                    ->query(fn ($query) => $query->whereHas('photos')),
                    
                Tables\Filters\Filter::make('started_at')
                    ->label('Дата начала')
                    ->form([
                        Forms\Components\DatePicker::make('started_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('started_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['started_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '>=', $date),
                            )
                            ->when(
                                $data['started_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\Filter::make('has_coordinates')
                    ->label('📍 Есть координаты')
                    ->query(fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('view_photos')
                    ->label('Фото')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->url(fn ($record) => self::getUrl('edit', [$record->id]) . '?activeRelationManager=0'),
                    
                Tables\Actions\Action::make('open_map')
                    ->label('Карта')
                    ->icon('heroicon-o-map')
                    ->color('success')
                    ->url(function ($record) {
                        if ($record->latitude && $record->longitude) {
                            return "https://www.google.com/maps?q={$record->latitude},{$record->longitude}";
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->hidden(fn ($record) => !$record->latitude || !$record->longitude),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->emptyStateHeading('Нет посещенных локаций')
            ->emptyStateDescription('Создайте первую посещенную локацию.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать посещенную локацию'),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitedLocations::route('/'),
            'create' => Pages\CreateVisitedLocation::route('/create'),
            'edit' => Pages\EditVisitedLocation::route('/{record}/edit'),
        ];
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'dispatcher', 'executor']);
    }
}
