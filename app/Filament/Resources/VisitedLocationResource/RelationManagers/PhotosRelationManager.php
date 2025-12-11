<?php

namespace App\Filament\Resources\VisitedLocationResource\RelationManagers;

use App\Models\Photo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Фотографии локации';
    protected static ?string $label = 'фотография';
    protected static ?string $pluralLabel = 'Фотографии';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('Фотография')
                    ->image()
                    ->directory('visited-location-photos')
                    ->maxSize(10240)
                    ->required()
                    ->preserveFilenames()
                    ->imagePreviewHeight('250')
                    ->loadingIndicatorPosition('left')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('right')
                    ->uploadButtonPosition('left')
                    ->uploadProgressIndicatorPosition('left'),
                    
                Forms\Components\TextInput::make('file_name')
                    ->label('Название файла')
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => 'photo_' . now()->format('Y-m-d_H-i-s')),
                    
                Forms\Components\TextInput::make('description')
                    ->label('Описание')
                    ->maxLength(65535)
                    ->nullable(),
                    
                Forms\Components\DateTimePicker::make('taken_at')
                    ->label('Время съемки')
                    ->required()
                    ->default(now()),
                    
                Forms\Components\TextInput::make('latitude')
                    ->label('Широта')
                    ->numeric()
                    ->step(0.000001)
                    ->nullable(),
                    
                Forms\Components\TextInput::make('longitude')
                    ->label('Долгота')
                    ->numeric()
                    ->step(0.000001)
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Фото')
                    ->size(80)
                    ->square(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('taken_at')
                    ->label('Время съемки')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Размер')
                    ->formatStateUsing(fn ($state) => $this->formatBytes($state))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Добавлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_coordinates')
                    ->label('Есть координаты')
                    ->query(fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить фотографию'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
                Tables\Actions\Action::make('view_full')
                    ->label('Просмотр')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn ($record) => view('filament.components.photo-modal', [
                        'photo' => $record,
                    ]))
                    ->modalHeading('Просмотр фотографии')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
