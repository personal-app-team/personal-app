<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompensationResource\Pages;
use App\Filament\Resources\CompensationResource\RelationManagers;
use App\Models\Compensation;
use App\Models\Shift;
use App\Models\MassPersonnelReport;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompensationResource extends Resource
{
    protected static ?string $model = Compensation::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Компенсации';
    protected static ?string $modelLabel = 'Компенсация';
    protected static ?string $pluralModelLabel = 'Компенсации';
    protected static ?string $navigationGroup = 'Финансы';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('compensatable_type')
                    ->label('Тип объекта')
                    ->required()
                    ->options([
                        Shift::class => 'Смена',
                        MassPersonnelReport::class => 'Отчет массового персонала',
                    ])
                    ->reactive()
                    ->afterStateUpdated(fn ($set) => $set('compensatable_id', null)),

                Forms\Components\Select::make('compensatable_id')
                    ->label('Объект')
                    ->required()
                    ->searchable()
                    ->options(function (callable $get) {
                        $type = $get('compensatable_type');
                        
                        if ($type === Shift::class) {
                            return Shift::with(['user', 'workRequest'])
                                ->get()
                                ->mapWithKeys(function ($shift) {
                                    $label = "Смена #{$shift->id}";
                                    if ($shift->user) {
                                        $label .= " - {$shift->user->name}";
                                    }
                                    if ($shift->workRequest) {
                                        $label .= " ({$shift->workRequest->title})";
                                    }
                                    return [$shift->id => $label];
                                });
                        }
                        
                        if ($type === MassPersonnelReport::class) {
                            return MassPersonnelReport::with(['workRequest'])
                                ->get()
                                ->mapWithKeys(function ($report) {
                                    $label = "Отчет масс. персонала #{$report->id}";
                                    if ($report->workRequest) {
                                        $label .= " ({$report->workRequest->title})";
                                    }
                                    return [$report->id => $label];
                                });
                        }
                        
                        return [];
                    })
                    ->getSearchResultsUsing(function (string $search, callable $get) {
                        $type = $get('compensatable_type');
                        
                        if ($type === Shift::class) {
                            return Shift::with(['user', 'workRequest'])
                                ->where('id', 'like', "%{$search}%")
                                ->orWhereHas('user', function ($query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($shift) {
                                    $label = "Смена #{$shift->id}";
                                    if ($shift->user) {
                                        $label .= " - {$shift->user->name}";
                                    }
                                    if ($shift->workRequest) {
                                        $label .= " ({$shift->workRequest->title})";
                                    }
                                    return [$shift->id => $label];
                                });
                        }
                        
                        if ($type === MassPersonnelReport::class) {
                            return MassPersonnelReport::with(['workRequest'])
                                ->where('id', 'like', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($report) {
                                    $label = "Отчет масс. персонала #{$report->id}";
                                    if ($report->workRequest) {
                                        $label .= " ({$report->workRequest->title})";
                                    }
                                    return [$report->id => $label];
                                });
                        }
                        
                        return [];
                    }),
                
                Forms\Components\Textarea::make('description')
                    ->label('Описание компенсации')
                    ->required()
                    ->columnSpanFull()
                    ->rows(3),
                
                Forms\Components\TextInput::make('requested_amount')
                    ->label('Запрошенная сумма')
                    ->required()
                    ->numeric()
                    ->prefix('₽')
                    ->default(0.00),
                
                Forms\Components\TextInput::make('approved_amount')
                    ->label('Утвержденная сумма')
                    ->required()
                    ->numeric()
                    ->prefix('₽')
                    ->default(0.00),
                
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->required()
                    ->options([
                        'pending' => 'На рассмотрении',
                        'approved' => 'Утверждено',
                        'rejected' => 'Отклонено',
                    ])
                    ->default('pending'),
                
                Forms\Components\Select::make('approved_by')
                    ->label('Утвердил')
                    ->relationship('approvedBy', 'name')
                    ->searchable()
                    ->preload(),
                
                Forms\Components\Textarea::make('approval_notes')
                    ->label('Комментарии при утверждении')
                    ->columnSpanFull()
                    ->rows(3),
                
                Forms\Components\DateTimePicker::make('approved_at')
                    ->label('Дата утверждения'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('compensatable_type')
                    ->label('Тип объекта')
                    ->formatStateUsing(fn ($state) => match($state) {
                        Shift::class => 'Смена',
                        MassPersonnelReport::class => 'Отчет масс. персонала',
                        default => class_basename($state)
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('compensatable_id')
                    ->label('Объект')
                    ->formatStateUsing(function ($state, Compensation $record) {
                        if ($record->compensatable_type === Shift::class) {
                            $shift = Shift::find($state);
                            return $shift ? "Смена #{$shift->id}" : "Смена #{$state}";
                        }
                        
                        if ($record->compensatable_type === MassPersonnelReport::class) {
                            $report = MassPersonnelReport::find($state);
                            return $report ? "Отчет масс. персонала #{$report->id}" : "Отчет #{$state}";
                        }
                        
                        return $state;
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                
                Tables\Columns\TextColumn::make('requested_amount')
                    ->label('Запрошено')
                    ->money('RUB')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('approved_amount')
                    ->label('Утверждено')
                    ->money('RUB')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'На рассмотрении',
                        'approved' => 'Утверждено',
                        'rejected' => 'Отклонено',
                    }),
                
                Tables\Columns\TextColumn::make('approvedBy.full_name')
                    ->label('Утвердил')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Дата утверждения')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'approved' => 'Утверждено',
                        'rejected' => 'Отклонено',
                    ]),
                
                Tables\Filters\SelectFilter::make('compensatable_type')
                    ->label('Тип объекта')
                    ->options([
                        'shift' => 'Смена',
                        'mass_personnel_report' => 'Отчет массового персонала',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompensation::route('/'),
            'create' => Pages\CreateCompensation::route('/create'),
            'edit' => Pages\EditCompensation::route('/{record}/edit'),
        ];
    }
}
