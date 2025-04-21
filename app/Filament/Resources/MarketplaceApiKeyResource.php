<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketplaceApiKeyResource\Pages;
use App\Models\MarketplaceApiKey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketplaceApiKeyResource extends Resource
{
    protected static ?string $modelLabel = 'API-ключ'; // Для одной записи
    protected static ?string $pluralModelLabel = 'API-ключи'; // Для списка записей
    protected static ?string $navigationLabel = 'Управление API-ключами'; // Название в меню
    protected static ?string $navigationGroup = 'Маркетплейсы'; // Группа в меню
    protected static ?string $createButtonLabel = 'Добавить API-ключ';

    protected static ?string $model = MarketplaceApiKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    private const fields = [
        'OZON' => 'OZON',
        'Wildberries' => 'Wildberries',
        'Яндекс Маркет' => 'Яндекс Маркет',
        'Мегамаркет' => 'Мегамаркет',
    ];

    public static function getTitle(): string
    {
        return 'Управление API-ключами'; // Название страницы
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('marketplace')->label('Маркетплейс')->options(self::fields)->reactive()->required(),
            TextInput::make('name')->label('Наименование')->required(),
            TextInput::make('base_api_url')->label('Base API URL'),
            TextInput::make('ozon_api_key')->label('OZON: Api-Key')->visible(fn(callable $get) => $get('marketplace') === 'OZON'),
            TextInput::make('ozon_client_id')->label('OZON: Client-Id')->visible(fn(callable $get) => $get('marketplace') === 'OZON'),
            TextInput::make('wb_api_key')->label('Wildberries: Api-Key')->visible(fn(callable $get) => $get('marketplace') === 'Wildberries'),
            TextInput::make('ym_api_key')->label('Яндекс Маркет: Api-Key')->visible(fn(callable $get) => $get('marketplace') === 'Яндекс Маркет'),
            TextInput::make('ym_campaign_id')->label('Яндекс Маркет: campaign_id')->visible(fn(callable $get) => $get('marketplace') === 'Яндекс Маркет'),
            TextInput::make('ym_business_id')->label('Яндекс Маркет: business_id')->visible(fn(callable $get) => $get('marketplace') === 'Яндекс Маркет'),
            TextInput::make('mm_api_key')->label('Мегамаркет: Api-Key')->visible(fn(callable $get) => $get('marketplace') === 'Мегамаркет'),
            TextInput::make('mm_cabinet_id')->label('Мегамаркет: Cabinet-Id')->visible(fn(callable $get) => $get('marketplace') === 'Мегамаркет')
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table->defaultPaginationPageOption(20)->paginationPageOptions([20,50,100,'all'])->columns([
            TextColumn::make('marketplace')->label('Маркетплейс'),
            TextColumn::make('name')->label('Наименование'),
            TextColumn::make('base_api_url')->label('Base API URL'),
        ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketplaceApiKeys::route('/'),
            'create' => Pages\CreateMarketplaceApiKey::route('/create'),
            'edit' => Pages\EditMarketplaceApiKey::route('/{record}/edit'),
        ];
    }
}
