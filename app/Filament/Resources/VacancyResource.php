<?php
// app/Filament/Resources/VacancyResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\VacancyResource\Pages;
use App\Filament\Resources\VacancyResource\RelationManagers;
use App\Models\Vacancy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VacancyResource extends Resource
{
    protected static ?string $model = Vacancy::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Подбор персонала';
    protected static ?string $navigationLabel = 'Вакансии';
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'вакансия';
    protected static ?string $pluralModelLabel = 'Вакансии';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Название вакансии')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('short_description')
                            ->label('Краткое описание')
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('employment_type')
                            ->label('Тип трудоустройства')
                            ->options([
                                'temporary' => 'Временный',
                                'permanent' => 'Постоянный',
                            ])
                            ->required(),
                        Forms\Components\Select::make('department_id')
                            ->label('Отдел')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('created_by_id')
                            ->label('Создатель')
                            ->relationship('createdBy', 'name')
                            ->default(auth()->id())
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активна', 
                                'closed' => 'Закрыта',
                            ])
                            ->default('active')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Отдел')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employment_type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => $state === 'temporary' ? 'Временный' : 'Постоянный')
                    ->badge()
                    ->color(fn ($state) => $state === 'temporary' ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 'active' ? 'Активна' : 'Закрыта'),
                Tables\Columns\TextColumn::make('recruitmentRequests.count')
                    ->label('Кол-во заявок')
                    ->counts('recruitmentRequests')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employment_type')
                    ->label('Тип трудоустройства')
                    ->options([
                        'temporary' => 'Временный',
                        'permanent' => 'Постоянный', 
                    ]),
                Tables\Filters\SelectFilter::make('department')
                    ->label('Отдел')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активна',
                        'closed' => 'Закрыта',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('close')
                    ->label('Закрыть')
                    ->icon('heroicon-o-lock-closed')
                    ->action(fn (Vacancy $record) => $record->close())
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Vacancy $record) => $record->status === 'active'),
                Tables\Actions\Action::make('reopen')
                    ->label('Открыть')
                    ->icon('heroicon-o-lock-open')
                    ->action(fn (Vacancy $record) => $record->reopen())
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Vacancy $record) => $record->status === 'closed'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Нет вакансий')
            ->emptyStateDescription('Создайте первую вакансию.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать вакансию'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\TasksRelationManager::class,
            // RelationManagers\RequirementsRelationManager::class, 
            // RelationManagers\ConditionsRelationManager::class,
            // RelationManagers\RecruitmentRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacancies::route('/'),
            'create' => Pages\CreateVacancy::route('/create'),
            'edit' => Pages\EditVacancy::route('/{record}/edit'),
        ];
    }
}
