<?php
namespace App\Filament\Resources\ContractorResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Пользователи подрядчика';
    protected static ?string $label = 'пользователя';
    protected static ?string $pluralLabel = 'Пользователи подрядчика';
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')->schema([
                Forms\Components\TextInput::make('name')->label('Имя')->required()->maxLength(255),
                Forms\Components\TextInput::make('surname')->label('Фамилия')->required()->maxLength(255),
                Forms\Components\TextInput::make('patronymic')->label('Отчество')->nullable()->maxLength(255),
                Forms\Components\TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                Forms\Components\TextInput::make('password')->label('Пароль')->password()->dehydrateStateUsing(fn ($state) => Hash::make($state))->dehydrated(fn ($state) => filled($state))->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\TextInput::make('phone')->label('Телефон')->tel()->required()->maxLength(20),
            ])->columns(2),
            Forms\Components\Section::make('Роли и доступ')->schema([
                Forms\Components\Select::make('roles')->label('Роли')->relationship('roles', 'name')->multiple()->preload()->searchable()->default(['contractor_executor'])->required(),
                Forms\Components\Toggle::make('email_verified_at')->label('Email подтвержден')->default(true)->dehydrateStateUsing(fn ($state) => $state ? now() : null),
            ]),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('full_name')->label('ФИО')->searchable(['surname', 'name', 'patronymic'])->sortable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('phone')->label('Телефон')->searchable(),
            Tables\Columns\TextColumn::make('roles.name')->label('Роли')->badge()->color('primary'),
            Tables\Columns\IconColumn::make('email_verified_at')->label('Подтвержден')->boolean()->trueIcon('heroicon-o-check-badge')->falseIcon('heroicon-o-x-mark'),
            Tables\Columns\TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\SelectFilter::make('roles')->label('Роль')->relationship('roles', 'name')->multiple()->preload(),
        ])->headerActions([
            Tables\Actions\CreateAction::make()->label('Добавить пользователя'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ])->emptyStateHeading('Нет пользователей')->emptyStateDescription('Добавьте первого пользователя для этого подрядчика.')->emptyStateActions([
            Tables\Actions\CreateAction::make()->label('Добавить пользователя'),
        ]);
    }
}
