<?php

namespace App\Filament\Resources\VacancyResource\RelationManagers;

use App\Models\RecruitmentRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecruitmentRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'recruitmentRequests';

    protected static ?string $title = 'Заявки на подбор';
    protected static ?string $label = 'заявка на подбор';
    protected static ?string $pluralLabel = 'Заявки на подбор';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Заявитель')
                    ->relationship('user', 'full_name')
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Заявитель')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('required_count')
                    ->label('Требуется')
                    ->sortable()
                    ->alignCenter(),
                    
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
                    
                Tables\Columns\TextColumn::make('candidates_count')
                    ->label('Кандидатов')
                    ->counts('candidates')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('hrResponsible.full_name')
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить заявку'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ред.'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
                Tables\Actions\Action::make('viewInResource')
                    ->label('Открыть')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => RecruitmentRequestResource::getUrl('edit', [$record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
