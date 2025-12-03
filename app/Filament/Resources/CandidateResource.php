<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidateResource\Pages;
use App\Filament\Resources\CandidateResource\RelationManagers;
use App\Models\Candidate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Подбор персонала';
    protected static ?string $navigationLabel = 'Кандидаты';
    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'кандидат';
    protected static ?string $pluralModelLabel = 'Кандидаты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('ФИО')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(20)
                            ->nullable(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Select::make('recruitment_request_id')
                            ->label('Заявка на подбор')
                            ->relationship('recruitmentRequest', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->vacancy?->title} - {$record->user->name}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->label('Источник')
                            ->options([
                                'hh' => 'HH.ru',
                                'linkedin' => 'LinkedIn',
                                'recruitment' => 'Рекрутинг',
                                'other' => 'Другое',
                            ])
                            ->default('other')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Процесс подбора')
                    ->schema([
                        Forms\Components\Select::make('expert_id')
                            ->label('Эксперт (заявитель)')
                            ->relationship('expert', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'new' => 'Новый',
                                'contacted' => 'Связались',
                                'sent_for_approval' => 'Отправлен на согласование',
                                'approved_for_interview' => 'Одобрен для собеседования',
                                'in_reserve' => 'В резерве',
                                'rejected' => 'Отклонен',
                            ])
                            ->default('new')
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('current_stage')
                            ->label('Текущий этап')
                            ->default('initial_contact')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('first_contact_date')
                            ->label('Дата первого контакта')
                            ->native(false)
                            ->nullable(),
                        Forms\Components\DatePicker::make('hr_contact_date')
                            ->label('Дата контакта с HR')
                            ->native(false)
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\FileUpload::make('resume_path')
                            ->label('Резюме')
                            ->directory('candidates/resumes')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240)
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('created_by_id')
                            ->default(auth()->id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recruitmentRequest.vacancy.title')
                    ->label('Вакансия')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->recruitmentRequest->vacancy?->title;
                    }),
                Tables\Columns\TextColumn::make('recruitmentRequest.user.full_name')
                    ->label('Заявитель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Источник')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'hh' => 'HH.ru',
                        'linkedin' => 'LinkedIn',
                        'recruitment' => 'Рекрутинг',
                        'other' => 'Другое',
                        default => $state
                    })
                    ->colors([
                        'hh' => 'info',
                        'linkedin' => 'primary',
                        'recruitment' => 'success',
                        'other' => 'gray',
                    ]),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'new' => 'Новый',
                        'contacted' => 'Связались',
                        'sent_for_approval' => 'На согласовании',
                        'approved_for_interview' => 'Одобрен',
                        'in_reserve' => 'В резерве',
                        'rejected' => 'Отклонен',
                        default => $state
                    })
                    ->colors([
                        'new' => 'gray',
                        'contacted' => 'info',
                        'sent_for_approval' => 'warning',
                        'approved_for_interview' => 'success',
                        'in_reserve' => 'primary',
                        'rejected' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('expert.full_name')
                    ->label('Эксперт')
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('first_contact_date')
                    ->label('Первый контакт')
                    ->date('d.m.Y')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('hr_contact_date')
                    ->label('Контак с HR')
                    ->date('d.m.Y')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Новый',
                        'contacted' => 'Связались',
                        'sent_for_approval' => 'На согласовании',
                        'approved_for_interview' => 'Одобрен',
                        'in_reserve' => 'В резерве',
                        'rejected' => 'Отклонен',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Источник')
                    ->options([
                        'hh' => 'HH.ru',
                        'linkedin' => 'LinkedIn',
                        'recruitment' => 'Рекрутинг',
                        'other' => 'Другое',
                    ]),
                Tables\Filters\SelectFilter::make('recruitment_request_id')
                    ->label('Заявка на подбор')
                    ->relationship('recruitmentRequest', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->vacancy?->title} - {$record->user->name}")
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('expert_id')
                    ->label('Эксперт')
                    ->relationship('expert', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_contacted')
                    ->label('Отметить связанным')
                    ->icon('heroicon-o-phone')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'contacted',
                            'hr_contact_date' => now(),
                        ]);
                    })
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'new'),
                Tables\Actions\Action::make('send_for_approval')
                    ->label('Отправить на согласование')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(function ($record) {
                        $record->update(['status' => 'sent_for_approval']);
                    })
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'contacted'),
                Tables\Actions\Action::make('approve_for_interview')
                    ->label('Одобрить собеседование')
                    ->icon('heroicon-o-check')
                    ->action(function ($record) {
                        $record->update(['status' => 'approved_for_interview']);
                    })
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'sent_for_approval'),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                    })
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !in_array($record->status, ['rejected', 'in_reserve'])),
                Tables\Actions\Action::make('reserve')
                    ->label('В резерв')
                    ->icon('heroicon-o-clock')
                    ->action(function ($record) {
                        $record->update(['status' => 'in_reserve']);
                    })
                    ->color('primary')
                    ->visible(fn ($record) => !in_array($record->status, ['rejected', 'in_reserve'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Нет кандидатов')
            ->emptyStateDescription('Создайте первого кандидата.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать кандидата'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CandidateStatusHistoryRelationManager::class,
            RelationManagers\CandidateDecisionsRelationManager::class,
            RelationManagers\InterviewsRelationManager::class,
            // RelationManagers\HiringDecisionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCandidates::route('/'),
            'create' => Pages\CreateCandidate::route('/create'),
            'edit' => Pages\EditCandidate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['recruitmentRequest.vacancy', 'recruitmentRequest.user', 'expert']);
    }
}
