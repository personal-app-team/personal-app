<?php

namespace App\Filament\Resources\RecruitmentRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CandidatesRelationManager extends RelationManager
{
    protected static string $relationship = 'candidates';

    protected static ?string $title = 'Кандидаты';

    public function form(Form $form): Form
    {
        return $form
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
                Forms\Components\DatePicker::make('first_contact_date')
                    ->label('Дата первого контакта')
                    ->native(false)
                    ->nullable(),
                Forms\Components\DatePicker::make('hr_contact_date')
                    ->label('Дата контакта с HR')
                    ->native(false)
                    ->nullable(),
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
                    ->required(),
                Forms\Components\TextInput::make('current_stage')
                    ->label('Текущий этап')
                    ->default('initial_contact')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->label('Заметки')
                    ->nullable()
                    ->columnSpanFull(),
                // Скрытые поля для автоматического заполнения
                Forms\Components\Hidden::make('created_by_id')
                    ->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
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
                Tables\Columns\TextColumn::make('expert.name')
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить кандидата')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by_id'] = auth()->id();
                        $data['current_stage'] = $data['current_stage'] ?? 'initial_contact';
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
