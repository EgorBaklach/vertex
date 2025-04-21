<?php namespace App\Filament\Resources\Dev\WB\Pages;

use App\Filament\Resources\Dev\WB\PropertiesResource;
use App\Models\Dev\WB\{CP, PPV, Properties, PV};
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertiesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table:truncate')
                ->requiresConfirmation()
                ->action(function()
                {
                    $db = DB::connection('dev');

                    $db->statement('SET FOREIGN_KEY_CHECKS=0;');

                    CP::query()->truncate();
                    PPV::query()->truncate();
                    PV::query()->truncate();
                    Properties::query()->truncate();

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
