<?php
// app/Filament/Resources/RecruitmentRequestResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\RecruitmentRequestResource\Pages;
use App\Filament\Resources\RecruitmentRequestResource\RelationManagers;
use App\Models\RecruitmentRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecruitmentRequestResource extends Resource
{
    protected static ?string $model = RecruitmentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Подбор персонала';
    protected static ?string $navigationLabel = 'Заявки на подбор';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'заявка на подбор';
    protected static ?string $pluralModelLabel = 'Заявки на подбор';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('vacancy_id')
                            ->label('Вакансия')
                            ->relationship('vacancy', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('user_id')
                            ->label('Заявитель')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('department_id')
                            ->label('Отдел')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Textarea::make('comment')
                            ->label('Комментарий')
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('required_count')
                            ->label('Требуемое количество')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                        Forms\Components\Select::make('employment_type')
                            ->label('Тип трудоустройства')
                            ->options([
                                'temporary' => 'Временный',
                                'permanent' => 'Постоянный',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Период с')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Период по (для временных)')
                            ->nullable()
                            ->native(false),
                    ])->columns(2),
                Forms\Components\Section::make('Управление заявкой')
                    ->schema([
                        Forms\Components\Select::make('hr_responsible_id')
                            ->label('Ответственный HR')
                            ->relationship('hrResponsible', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'new' => 'Новая',
                                'assigned' => 'Назначена', 
                                'in_progress' => 'В работе',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->default('new')
                            ->required(),
                        Forms\Components\Select::make('urgency')
                            ->label('Срочность')
                            ->options([
                                'low' => 'Низкая',
                                'medium' => 'Средняя',
                                'high' => 'Высокая',
                            ])
                            ->default('medium')
                            ->required(),
                        Forms\Components\DatePicker::make('deadline')
                            ->label('Крайний срок закрытия')
                            ->required()
                            ->native(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vacancy.title')
                    ->label('Вакансия')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Заявитель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Отдел')
                    ->sortable(),
                Tables\Columns\TextColumn::make('required_count')
                    ->label('Требуется')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employment_type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => $state === 'temporary' ? 'Временный' : 'Постоянный')
                    ->badge()
                    ->color(fn ($state) => $state === 'temporary' ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'new' => 'gray',
                        'assigned' => 'info',
                        'in_progress' => 'primary', 
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'new' => 'Новая',
                        'assigned' => 'Назначена',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('urgency')
                    ->label('Срочность')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'low' => 'Низкая',
                        'medium' => 'Средняя', 
                        'high' => 'Высокая',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Срок')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('hrResponsible.name')
                    ->label('Ответственный HR')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Новая',
                        'assigned' => 'Назначена',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ]),
                Tables\Filters\SelectFilter::make('urgency')
                    ->label('Срочность')
                    ->options([
                        'low' => 'Низкая',
                        'medium' => 'Средняя',
                        'high' => 'Высокая',
                    ]),
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
                Tables\Filters\SelectFilter::make('hr_responsible_id')
                    ->label('Ответственный HR')
                    ->relationship('hrResponsible', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('overdue')
                    ->label('Просроченные')
                    ->query(fn ($query) => $query->overdue()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assignToMe')
                    ->label('Взять в работу')
                    ->icon('heroicon-o-user-plus')
                    ->action(function (RecruitmentRequest $record) {
                        $record->assignToHr(auth()->user());
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RecruitmentRequest $record) => 
                        $record->status === 'new' && 
                        auth()->user()->hasRole(['hr', 'head_hr'])
                    ),
                Tables\Actions\Action::make('startProgress')
                    ->label('Начать работу')
                    ->icon('heroicon-o-play')
                    ->action(fn (RecruitmentRequest $record) => $record->startProgress())
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (RecruitmentRequest $record) => 
                        $record->status === 'assigned' && 
                        $record->hr_responsible_id === auth()->id()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Нет заявок на подбор')
            ->emptyStateDescription('Создайте первую заявку на подбор.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать заявку на подбор'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CandidatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecruitmentRequests::route('/'),
            'create' => Pages\CreateRecruitmentRequest::route('/create'),
            'edit' => Pages\EditRecruitmentRequest::route('/{record}/edit'),
        ];
    }
}
