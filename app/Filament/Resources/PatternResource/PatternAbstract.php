<?php namespace App\Filament\Resources\PatternResource;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

abstract class PatternAbstract extends Resource
{
    protected static ?string $modelLabel = 'Шаблон';
    protected static ?string $pluralModelLabel = 'Шаблоны';

    protected static ?string $navigationLabel = 'Шаблоны';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('pattern')->label('Шаблон')->required(),
            TextInput::make('description')->label('Описание')->required(),
            Select::make('relation')->options(static::getFields())->reactive()->label('Структура')->required()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pattern')->verticalAlignment(VerticalAlignment::Start)->label('Шаблон'),
                TextColumn::make('description')->verticalAlignment(VerticalAlignment::Start)->label('Описание'),
                TextColumn::make('relation')->verticalAlignment(VerticalAlignment::Start)->label('Структура')
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
            ]);
    }

    abstract protected static function getFields(): array;

    public static function getPages(): array
    {
        return [
            'index' => static::list::route('/')
        ];
    }
}
