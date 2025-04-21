<?php namespace App\Filament\Resources\Dev\WB\Pages;

use App\Filament\Resources\Dev\WB\ProductsResource;
use App\Models\Dev\WB\{FBOAmounts, FBOStocks, FBSAmounts, FBSOffices, FBSStocks, Files, PPV, Prices, Products, PV, Settings, Sizes};
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset:sort')
                ->action(fn() => call_user_func(
                    fn() => $this->callHook('refresh'),
                    $this->tableSortColumn = null,
                    $this->tableSortDirection = null,
                    $this->tableGrouping = $this->table->getDefaultGroup()->getColumn(),
                    $this->tableGroupingDirection = 'asc'
                ))
                ->label('Сбросить сортировку'),
            Action::make('table:truncate')
                ->requiresConfirmation()
                ->action(function()
                {
                    $db = DB::connection('dev');

                    $db->statement('SET FOREIGN_KEY_CHECKS=0;');

                    foreach([Prices::class, Sizes::class, Files::class, PPV::class, FBOAmounts::class, FBOStocks::class, FBSOffices::class, FBSStocks::class, FBSAmounts::class] as $class)
                    {
                        /** @var Model $class */ $class::query()->truncate();
                    }

                    PV::query()->whereNotIn('pid', Settings::query()->whereLike('variable', '%pid')->pluck('value')->all() ?? [])->delete();

                    Products::query()->truncate();

                    $db->statement('SET FOREIGN_KEY_CHECKS=1;');
                })
                ->modalHeading('Подтвердите действие')
                ->label('Очистить таблицу'),
            Action::make('reload')
                ->action(fn () => $this->callHook('refresh'))
                ->label('Обновить страницу')
        ];
    }
}
