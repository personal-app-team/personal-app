<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HiringDecisionResource\Pages;
use App\Filament\Resources\HiringDecisionResource\RelationManagers;
use App\Models\HiringDecision;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HiringDecisionResource extends Resource
{
    protected static ?string $model = HiringDecision::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = '🎯 Подбор персонала';
    protected static ?string $navigationLabel = 'Решения о приеме';
    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'решение о приеме';
    protected static ?string $pluralModelLabel = 'Решения о приеме';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Кандидат и вакансия')
                    ->schema([
                        Forms\Components\Select::make('candidate_id')
                            ->label('Кандидат')
                            ->relationship('candidate', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('position_title')
                            ->label('Должность')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('specialty_id')
                            ->label('Специальность')
                            ->relationship('specialty', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
                Forms\Components\Section::make('Условия трудоустройства')
                    ->schema([
                        Forms\Components\Select::make('employment_type')
                            ->label('Тип трудоустройства')
                            ->options([
                                'temporary' => 'Временный',
                                'permanent' => 'Постоянный',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('payment_type')
                            ->label('Тип оплаты')
                            ->options([
                                'rate' => 'Ставка',
                                'salary' => 'Оклад',
                                'combined' => 'Комбинированный',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('payment_value')
                            ->label('Сумма оплаты')
                            ->numeric()
                            ->required()
                            ->prefix('₽'),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата окончания (для временных)')
                            ->nullable()
                            ->native(false)
                            ->visible(fn (callable $get) => $get('employment_type') === 'temporary'),
                        Forms\Components\TextInput::make('trainee_period_days')
                            ->label('Испытательный срок (дней)')
                            ->numeric()
                            ->nullable()
                            ->minValue(1),
                    ])->columns(2),
                Forms\Components\Section::make('Утверждение')
                    ->schema([
                        Forms\Components\Select::make('approved_by_id')
                            ->label('Утверждающий')
                            ->relationship('approvedBy', 'name')
                            ->default(auth()->id())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'approved' => 'Утверждено',
                                'rejected' => 'Отклонено',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\Select::make('decision_makers')
                            ->label('Принимающие решение')
                            ->multiple()
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Кандидат')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position_title')
                    ->label('Должность')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employment_type')
                    ->label('Тип трудоустройства')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'temporary' => 'Временный',
                        'permanent' => 'Постоянный',
                        default => $state
                    })
                    ->colors([
                        'temporary' => 'warning',
                        'permanent' => 'success',
                    ]),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Тип оплаты')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'rate' => 'Ставка',
                        'salary' => 'Оклад',
                        'combined' => 'Комбинированный',
                        default => $state
                    }),
                Tables\Columns\TextColumn::make('payment_value')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Черновик',
                        'approved' => 'Утверждено',
                        'rejected' => 'Отклонено',
                        default => $state
                    })
                    ->colors([
                        'draft' => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('approvedBy.full_name')
                    ->label('Утверждающий'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'approved' => 'Утверждено',
                        'rejected' => 'Отклонено',
                    ]),
                Tables\Filters\SelectFilter::make('employment_type')
                    ->label('Тип трудоустройства')
                    ->options([
                        'temporary' => 'Временный',
                        'permanent' => 'Постоянный',
                    ]),
                Tables\Filters\SelectFilter::make('candidate')
                    ->label('Кандидат')
                    ->relationship('candidate', 'full_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Утвердить')
                    ->icon('heroicon-o-check')
                    ->action(fn (HiringDecision $record) => $record->approve())
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (HiringDecision $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn (HiringDecision $record) => $record->reject())
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (HiringDecision $record) => $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Нет решений о приеме')
            ->emptyStateDescription('Создайте первое решение о приеме.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать решение о приеме'),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListHiringDecisions::route('/'),
            'create' => Pages\CreateHiringDecision::route('/create'),
            'edit' => Pages\EditHiringDecision::route('/{record}/edit'),
        ];
    }
}
