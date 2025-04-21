<?php namespace App\Filament\Resources\Dev\YM\Pages;

use App\Contexts\YMCProperties;
use App\Filament\Resources\Dev\Ym\ProductsResource;
use App\Models\Dev\YM\CP;
use App\Models\Dev\YM\Products;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;

class ListProducts extends CTPAbstract
{
    protected static string $resource = ProductsResource::class;

    public function getTableRecords(): Collection | Paginator | CursorPaginator
    {
        /**
         * @var YMCProperties $YMCProperties
         * @var Products $product
         */

        $products = parent::getTableRecords(); $YMCProperties = App::make(YMCProperties::class); foreach($products as $product) foreach($product->ppvs as $ppv) $YMCProperties->ppv($ppv);

        if(count($YMCProperties->pids ?? [])) foreach(CP::query()->whereIn('pid', $YMCProperties->pids)->get() as $cp) $YMCProperties->cp($cp); return $products;
    }
}
