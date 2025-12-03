<?php

namespace App\Filament\Resources\CandidateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CandidateStatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'candidateStatusHistory';

    protected static ?string $title = 'История статусов';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->required(),
                Forms\Components\Textarea::make('comment')
                    ->label('Комментарий')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\Select::make('changed_by_id')
                    ->label('Кто изменил')
                    ->relationship('changedBy', 'name')
                    ->default(auth()->id())
                    ->required(),
                Forms\Components\Hidden::make('previous_status'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
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
                Tables\Columns\TextColumn::make('previous_status')
                    ->label('Предыдущий статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'new' => 'Новый',
                        'contacted' => 'Связались',
                        'sent_for_approval' => 'На согласовании',
                        'approved_for_interview' => 'Одобрен',
                        'in_reserve' => 'В резерве',
                        'rejected' => 'Отклонен',
                        null => '—',
                        default => $state
                    })
                    ->colors([
                        'new' => 'gray',
                        'contacted' => 'info',
                        'sent_for_approval' => 'warning',
                        'approved_for_interview' => 'success',
                        'in_reserve' => 'primary',
                        'rejected' => 'danger',
                    ])
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('changedBy.full_name')
                    ->label('Кто изменил')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->comment;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата изменения')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить запись истории')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['previous_status'] = $this->getOwnerRecord()->status;
                        return $data;
                    })
                    ->after(function ($record) {
                        // Автоматически обновляем статус кандидата при создании записи истории
                        $this->getOwnerRecord()->update(['status' => $record->status]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
