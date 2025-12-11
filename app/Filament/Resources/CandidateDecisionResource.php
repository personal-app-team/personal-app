<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidateDecisionResource\Pages;
use App\Models\CandidateDecision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CandidateDecisionResource extends Resource
{
    protected static ?string $model = CandidateDecision::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = '🎯 Подбор персонала';
    protected static ?string $navigationLabel = 'Решения по кандидатам';
    protected static ?int $navigationSort = 70;

    protected static ?string $modelLabel = 'решение по кандидату';
    protected static ?string $pluralModelLabel = 'Решения по кандидатам';

    // Можно скрыть из навигации, так как доступ через RelationManager
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('candidate_id')
                            ->label('Кандидат')
                            ->relationship('candidate', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        
                        Forms\Components\Select::make('user_id')
                            ->label('Заявитель')
                            ->relationship('user', 'full_name')
                            ->default(auth()->id())
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\Select::make('decision')
                            ->label('Решение')
                            ->options([
                                'reject' => 'Отклонить',
                                'reserve' => 'В резерв',
                                'interview' => 'Собеседование',
                                'other_vacancy' => 'Другая вакансия',
                            ])
                            ->required()
                            ->live(),
                        
                        Forms\Components\DatePicker::make('decision_date')
                            ->label('Дата решения')
                            ->default(now())
                            ->required()
                            ->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Комментарий')
                    ->schema([
                        Forms\Components\Textarea::make('comment')
                            ->label('Комментарий')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Кандидат')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => CandidateResource::getUrl('edit', [$record->candidate_id]))
                    ->openUrlInNewTab(),
                
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Заявитель')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('decision')
                    ->label('Решение')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'reject' => 'Отклонить',
                        'reserve' => 'В резерв',
                        'interview' => 'Собеседование',
                        'other_vacancy' => 'Другая вакансия',
                        default => $state
                    })
                    ->colors([
                        'reject' => 'danger',
                        'reserve' => 'warning',
                        'interview' => 'success',
                        'other_vacancy' => 'info',
                    ]),
                
                Tables\Columns\TextColumn::make('decision_date')
                    ->label('Дата решения')
                    ->date('d.m.Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->comment)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('decision')
                    ->label('Решение')
                    ->options([
                        'reject' => 'Отклонить',
                        'reserve' => 'В резерв',
                        'interview' => 'Собеседование',
                        'other_vacancy' => 'Другая вакансия',
                    ]),
                
                Tables\Filters\SelectFilter::make('candidate_id')
                    ->label('Кандидат')
                    ->relationship('candidate', 'full_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Заявитель')
                    ->relationship('user', 'full_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\Filter::make('decision_date')
                    ->label('Дата решения')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('until')
                            ->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('decision_date', '>=', $date)
                            )
                            ->when($data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('decision_date', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_candidate')
                    ->label('К кандидату')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => CandidateResource::getUrl('edit', [$record->candidate_id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Нет решений по кандидатам')
            ->emptyStateDescription('Создайте первое решение или работайте через карточку кандидата.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать решение'),
            ])
            ->defaultSort('decision_date', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers если понадобятся
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCandidateDecisions::route('/'),
            'create' => Pages\CreateCandidateDecision::route('/create'),
            'edit' => Pages\EditCandidateDecision::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['candidate', 'user'])
            ->latest();
    }
}
