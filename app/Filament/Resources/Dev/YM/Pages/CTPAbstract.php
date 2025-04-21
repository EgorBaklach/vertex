<?php namespace App\Filament\Resources\Dev\YM\Pages;

use App\Models\Dev\YM\{Categories,
    CommodityCodes,
    CP,
    Docs,
    FBSAmounts,
    FBSStocks,
    FBYAmounts,
    FBYStocks,
    Notices,
    PCC,
    PPV,
    Prices,
    Products,
    Properties,
    PU,
    PV,
    Rating,
    Recommendations,
    Restrictions,
    SellingPrograms,
    Times,
    Units};
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class CTPAbstract extends ListRecords
{
    private const classes = [

        // DYNAMIC

        FBSAmounts::class,
        FBYAmounts::class,

        SellingPrograms::class,
        Notices::class,
        Prices::class,
        PCC::class,
        PPV::class,
        Docs::class,
        Times::class,
        Rating::class,
        Recommendations::class,

        Restrictions::class,
        CP::class,
        PV::class,
        PU::class,

        // STATIC

        FBSStocks::class,
        FBYStocks::class,

        Products::class,
        CommodityCodes::class,

        Units::class,
        Properties::class,
        Categories::class
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
