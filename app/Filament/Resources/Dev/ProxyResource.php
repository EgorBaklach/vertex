<?php namespace App\Filament\Resources\Dev;

use App\Filament\Clusters\Dev;
use App\Models\Dev\Proxies;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions;
use Filament\Tables\Table;

class ProxyResource extends Resource
{
    protected static ?string $model = Proxies::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Прокси адрес';
    protected static ?string $pluralModelLabel = 'Прокси-лист';

    protected static ?string $navigationLabel = 'Прокси-лист';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'GENERAL';

    public static function getTitle(): string
    {
        return 'Управление Проксями';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('active')->options(['Y' => 'Y'])->placeholder('N')->reactive()->label('Вкл'),
            TextInput::make('ip')->required()->label('Адрес'),
            TextInput::make('port')->required()->label('Порт'),
            TextInput::make('user')->required()->label('Логин'),
            TextInput::make('pass')->required()->label('Пароль'),
            Select::make('type')->options(['residential' => 'Стационарные', 'mobile' => 'Мобильные'])->reactive()->required()->label('Тип прокси'),
            TextInput::make('country')->required()->label('Страна'),
            TextInput::make('provider')->required()->label('Поставщик')
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table->paginated(false)->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('active')->placeholder('NULL')->label('Вкл'),
            TextColumn::make('ip')->label('Адрес'),
            TextColumn::make('port')->label('Порт'),
            TextColumn::make('user')->label('Логин'),
            TextColumn::make('pass')->label('Пароль'),
            TextColumn::make('type')->sortable()->formatStateUsing(fn($state) => match($state){'residential' => 'Стационарные', 'mobile' => 'Мобильные'})->label('Тип прокси'),
            TextColumn::make('country')->sortable()->label('Страна'),
            TextColumn::make('provider')->sortable()->label('Поставщик'),
            TextColumn::make('process')->label('В работе'),
            TextColumn::make('success')->label('Успешно'),
            TextColumn::make('abort')->label('Отказ'),
            TextColumn::make('last_request')->placeholder('NULL'),
        ])->actions([
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProxyResource::route('/')
        ];
    }
}
