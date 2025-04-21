<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasicProductResource\Pages;
use App\Models\BasicProduct;
use App\Models\CategoryWb;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class BasicProductResource extends Resource
{
    protected static ?string $model = BasicProduct::class;

    protected static ?string $navigationGroup = 'Маркетплейсы';
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $label = 'Базовый товар';
    protected static ?string $pluralLabel = 'Базовые товары';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('Категория')
                ->options(CategoryWb::pluck('subject_name', 'id')->toArray())
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('article')->label('Артикул')->required(),
            Forms\Components\TextInput::make('product_name')->label('Название товара')->required(),

            Forms\Components\TextInput::make('price_with_discount')
                ->label('Цена с учетом скидок')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('price_without_discount')
                ->label('Цена до скидок')
                ->numeric()
                ->required(),
				
			Forms\Components\TextInput::make('minimum_price')
				->label('Минимальная цена продажи')
				->numeric()
				->required(),

            Forms\Components\TextInput::make('package_length_mm')->label('Длина упаковки (мм)')->numeric()->required(),
            Forms\Components\TextInput::make('package_height_mm')->label('Высота упаковки (мм)')->numeric()->required(),
            Forms\Components\TextInput::make('package_width_mm')->label('Ширина упаковки (мм)')->numeric()->required(),
            Forms\Components\TextInput::make('package_weight_g')->label('Вес упаковки (г)')->numeric()->required(),

            Forms\Components\Select::make('currency')
                ->label('Валюта')
                ->options([
                    'Российский рубль' => 'Российский рубль',
                    'Белорусский рубль' => 'Белорусский рубль',
                ])
                ->required(),

            Forms\Components\Select::make('vat')
                ->label('НДС')
                ->options([
                    'Без НДС' => 'Без НДС',
                    '10%' => '10%',
                    '20%' => '20%',
                    'УСН 5%' => 'УСН 5%',
                    'УСН 7%' => 'УСН 7%',
                ])
                ->required(),

            Forms\Components\Textarea::make('description')->label('Описание')->required(),

            Forms\Components\Toggle::make('is_size_based')
                ->label('Размерный товар')
                ->reactive(),

            Forms\Components\Select::make('size_chart')
                ->label('Размерная сетка')
                ->options([
                    'Детская' => 'Детская',
                    'Мужская' => 'Мужская',
                    'Женская' => 'Женская',
                ])
                ->hidden(fn ($get) => !$get('is_size_based')), // Отображается только если is_size_based = true
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.subject_name')->label('Категория')->searchable(),
                Tables\Columns\TextColumn::make('article')->label('Артикул')->searchable(),
                Tables\Columns\TextColumn::make('product_name')->label('Название товара')->searchable(),
                Tables\Columns\TextColumn::make('price_with_discount')->label('Цена со скидкой')->sortable(),
                Tables\Columns\TextColumn::make('price_without_discount')->label('Цена без скидки')->sortable(),
				Tables\Columns\TextColumn::make('minimum_price')->label('Минимальная цена продажи')->sortable(),
                Tables\Columns\TextColumn::make('currency')->label('Валюта'),
                Tables\Columns\TextColumn::make('vat')->label('НДС'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBasicProducts::route('/'),
            'create' => Pages\CreateBasicProduct::route('/create'),
            'edit' => Pages\EditBasicProduct::route('/{record}/edit'),
        ];
    }
}

