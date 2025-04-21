<?php namespace App\Filament\Resources\Dev\OZON\Pages;

use App\Models\Dev\OZON\{Categories,
    Commissions,
    CT,
    CTP,
    Errors,
    FBOAmounts,
    FBOStocks,
    Files,
    Indexes,
    PPV,
    Prices,
    Products,
    Properties,
    PV,
    Statuses,
    Types};
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class CTPAbstract extends ListRecords
{
    private const classes = [
        CT::class, CTP::class, Properties::class, Categories::class,
        Types::class, PV::class, Products::class, PPV::class, Files::class,
        Prices::class, Commissions::class, Errors::class, Indexes::class, Statuses::class,
        FBOAmounts::class, FBOStocks::class
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table:truncate')
                ->requiresConfirmation()
                ->action(function()
                {
                    $db = DB::connection('dev'); $db->statement('SET FOREIGN_KEY_CHECKS=0;'); /** @var Model $class */

                    foreach(self::classes as $class) $class::query()->truncate();

                    $db->statement('SET FOREIGN_KEY_CHECKS=1;');
                })
                ->modalHeading('Подтвердите действие')
                ->label('Очистить данные'),
            Action::make('reload')
                ->action(fn () => $this->callHook('refresh'))
                ->label('Обновить страницу')
        ];
    }
}
