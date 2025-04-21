<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WbOrderResource\Pages;
use App\Models\WbOrder;
use App\Models\WbOrderStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class WbOrderResource extends Resource
{
    protected static ?string $model = WbOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Заказы WB';

    protected static ?string $navigationGroup = 'Wildberries';

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('id')
                ->label('ID заказа')
                ->disabled(),
            Forms\Components\TextInput::make('created_at')
                ->label('Дата создания')
                ->disabled(),
            Forms\Components\TextInput::make('token.name')
                ->label('Название токена')
                ->disabled(),
            Forms\Components\TextInput::make('article')
                ->label('Артикул')
                ->disabled(),
            Forms\Components\TextInput::make('price')
                ->label('Цена (в валюте продажи)')
                ->formatStateUsing(fn($state) => $state ? number_format($state / 100, 2, '.', '') : 'Не указано')
                ->disabled(),
            Forms\Components\TextInput::make('converted_price')
                ->label('Цена (RUB)')
                ->formatStateUsing(fn($state) => $state ? number_format($state / 100, 2, '.', '') : 'Не указано')
                ->disabled(),
            Forms\Components\TextInput::make('currency_code')
                ->label('Код валюты продажи')
                ->disabled(),
            Forms\Components\TextInput::make('converted_currency_code')
                ->label('Код валюты после конвертации')
                ->disabled(),
            Forms\Components\TextInput::make('supplier_status')
                ->label('Статус сборки')
                ->disabled(),
            Forms\Components\TextInput::make('wb_status')
                ->label('Статус WB')
                ->disabled(),
            Forms\Components\TextInput::make('delivery_type')
                ->label('Тип доставки')
                ->disabled(),
            Forms\Components\TextInput::make('supply_id')
                ->label('Поставка')
                ->disabled(),
            Forms\Components\TextInput::make('order_uid')
                ->label('UID заказа')
                ->disabled(),
            Forms\Components\Textarea::make('skus')
                ->label('SKUs')
                ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : 'Не указано')
                ->disabled(),
            Forms\Components\Textarea::make('offices')
                ->label('Склад отгрузки')
                ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : 'Не указано')
                ->disabled(),
            Forms\Components\Textarea::make('address')
                ->label('Адрес')
                ->disabled(),
            Forms\Components\Textarea::make('comment')
                ->label('Комментарий')
                ->disabled(),
            Forms\Components\TextInput::make('warehouse_id')
                ->label('ID склада')
                ->disabled(),
            Forms\Components\TextInput::make('nm_id')
                ->label('NM ID')
                ->disabled(),
            Forms\Components\TextInput::make('chrt_id')
                ->label('CHRT ID')
                ->disabled(),
            Forms\Components\TextInput::make('cargo_type')
                ->label('Тип груза')
                ->disabled(),
            Forms\Components\TextInput::make('is_zero_order')
                ->label('Нулевой заказ')
                ->formatStateUsing(fn($state) => $state ? 'Да' : 'Нет')
                ->disabled(),
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('ID заказа')
                ->searchable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Дата создания')
                ->dateTime(),
            Tables\Columns\TextColumn::make('article')
                ->label('Артикул')
                ->searchable(),
            Tables\Columns\TextColumn::make('converted_price')
                ->label('Цена (RUB)')
                ->formatStateUsing(fn($state) => $state ? number_format($state / 100, 2, '.', '') : 'Не указано')
                ->sortable(),
            Tables\Columns\TextColumn::make('token.name')
                ->label('Название токена')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('supplierStatusDescription.description')
                ->label('Статус сборки')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('wbStatusDescription.description')
                ->label('Статус WB')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('delivery_type')
                ->label('Тип доставки'),
            Tables\Columns\TextColumn::make('supply_id')
                ->label('Поставка'),
            Tables\Columns\TextColumn::make('order_uid')
                ->label('UID заказа'),
            Tables\Columns\TextColumn::make('skus')
                ->label('SKUs')
                ->formatStateUsing(function ($state) {
                    return is_string($state) ? $state : 'Не указано';
                }),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('token_id')
                ->options(
                    DB::table('marketplace_api_keys')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->label('Токены')
                ->multiple(),
            Tables\Filters\SelectFilter::make('supplier_status')
                ->options(
                    WbOrderStatus::where('status_type', 'supplierStatus')
                        ->pluck('description', 'code')
                        ->toArray()
                )
                ->label('Статус сборки')
                ->multiple(),
        ])
        ->header(function () {
            // Общая статистика
            $overallStatistics = DB::table('wb_orders')
                ->selectRaw("
                    SUM(CASE WHEN supplier_status IN ('new', 'confirm') THEN 1 ELSE 0 END) as in_work,
                    SUM(CASE WHEN supplier_status = 'confirm' THEN 1 ELSE 0 END) as on_assembly,
                    SUM(CASE WHEN supplier_status = 'new' THEN 1 ELSE 0 END) as new_orders
                ")
                ->first();

            // Статистика по токенам
            $statistics = DB::table('wb_orders as wo')
                ->join('marketplace_api_keys as mk', 'wo.token_id', '=', 'mk.id')
                ->leftJoin('wb_order_statuses as ss', function ($join) {
                    $join->on('wo.supplier_status', '=', 'ss.code')
                         ->where('ss.status_type', '=', 'supplierStatus');
                })
                ->select(
                    'mk.id as token_id',
                    'mk.name as token_name',
                    'ss.description as supplier_status',
                    'ss.code as supplier_status_code',
                    DB::raw('COUNT(*) as total_orders')
                )
                ->groupBy('mk.id', 'mk.name', 'ss.description', 'ss.code')
                ->get();

            return view('components.wb-order-statistics', compact('overallStatistics', 'statistics'));
        })
        ->headerActions([
            Tables\Actions\Action::make('loadOrders')
                ->label('Загрузить заказы')
                ->color('success')
                ->icon('heroicon-o-cloud-arrow-down')
                ->action(function () {
                    Artisan::call('fetch:wb-orders');
                    return redirect()->back()->with('success', 'Заказы успешно загружены.');
                })
                ->requiresConfirmation()
                ->modalHeading('Подтверждение загрузки')
                ->modalSubheading('Вы действительно хотите загрузить заказы из Wildberries?'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
}


    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWbOrders::route('/'),
            'view' => Pages\ViewWbOrder::route('/{record}'),
        ];
    }
public static function getOrderStatistics(): array
{
    return DB::table('wb_orders')
        ->select(
            'token_id',
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('supplier_status as status'),
            DB::raw('(SELECT name FROM marketplace_api_keys WHERE marketplace_api_keys.id = wb_orders.token_id) as token_name')
        )
        ->groupBy('token_id', 'supplier_status')
        ->get()
        ->groupBy('token_name')
        ->map(function ($group) {
            return $group->mapWithKeys(function ($item) {
                return [$item->status => $item->total_orders];
            });
        })
        ->toArray();
}
}
