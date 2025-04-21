<?php namespace App\Filament\Resources\Dev;

use App\Filament\Clusters\Dev;
use App\Helpers\Func;
use App\Models\Dev\MarketplaceApiKey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class MarketplaceApiKeyResource extends Resource
{
    protected static ?string $model = MarketplaceApiKey::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'API-ключ';
    protected static ?string $pluralModelLabel = 'API-ключи'; // Для списка записей

    protected static ?string $navigationLabel = 'API-ключи'; // Название в меню
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'GENERAL';

    private const counts = 2;

    public static function getTitle(): string
    {
        return 'Управление API-ключами';
    }

    private static function read_wb_token(string $token): array|bool
    {
        [$first, $second] = explode('.', $token); $info = []; $attempts = 0;

        foreach(compact('first', 'second') as $name => $part) while(true)
        {
            if(is_array($info[$name] = json_decode(base64_decode($part), true, 512, JSON_BIGINT_AS_STRING)) || ++$attempts >= self::counts) break; $part .= '=';
        }
        return $info;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('active')->options(['Y' => 'Y'])->placeholder('N')->reactive()->label('Вкл'),
            Select::make('marketplace')->options(['OZON' => 'OZON', 'WB' => 'Wildberries', 'YM' => 'Яндекс маркет'])->reactive()->required()->label('Маркетплейс'),
            TextInput::make('name')->required()->label('Наименование'),
            TextInput::make('token')->required()->label('Api-Key')
        ]);
    }

    private static function decode(array $array, $level = 0): string
    {
        return implode('<br>', Arr::map($array, fn($v, $k) => str_repeat('- ', $level).'**'.$k.'**: '.(is_array($v) ? '<br>'.self::decode($v, ++$level) : $v)));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->recordAction(null)
            ->groups([Group::make('marketplace')->titlePrefixedWithLabel(false)])
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('active')->label('Вкл')->placeholder('NULL'),
                TextColumn::make('marketplace')->label('Маркетплейс'),
                TextColumn::make('name')->label('Наименование'),
                TextColumn::make('token')->extraAttributes(['class' => 'max-w-xs break-words', 'style' => 'overflow: hidden'])->label('Api-Key'),
                TextColumn::make('info')
                    ->getStateUsing(fn(MarketplaceApiKey $token) => match($token->marketplace)
                    {
                        'WB' => new HtmlString(Func::call(self::read_wb_token($token->token), fn(array $data) => Func::call($data['second'], fn(array $second) => implode('<br>', [
                            '**ID**: '.$second['id'],
                            '**SID**: '.$second['sid'],
                            '**Expired**: '.date('Y-m-d H:i:s', $second['exp'])
                        ])))),
                        default => is_null($token->params) ? null : new HtmlString(self::decode($token->params))
                    })
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->placeholder('NULL')
                    ->markdown()
                    ->label('Параметры'),
                TextColumn::make('process')->label('В работе'),
                TextColumn::make('success')->label('Успешно'),
                TextColumn::make('abort')->label('Отказ'),
                TextColumn::make('last_request')->placeholder('NULL'),
            ])
            ->filters([
                SelectFilter::make('market')->options(['WB' => 'WB', 'OZON' => 'OZON', 'YM' => 'YM'])->attribute('marketplace')->label('Маркетплейс')
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make()
            ])
            ->paginationPageOptions([50,100,'all'])
            ->defaultPaginationPageOption(50)
            ->defaultGroup('marketplace');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketplaceApiKeys::route('/')
        ];
    }
}
