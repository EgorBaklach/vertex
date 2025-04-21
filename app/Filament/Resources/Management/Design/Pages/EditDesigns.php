<?php namespace App\Filament\Resources\Management\Design\Pages;

use App\Filament\Resources\Management\Design\DesignsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditDesigns extends EditRecord
{
    protected static string $resource = DesignsResource::class;

    protected function fillFormWithDataAndCallHooks(Model $record, array $extraData = []): void
    {
        Log::channel('design')->info(json_encode($extraData));

        parent::fillFormWithDataAndCallHooks($record, $extraData);
    }

    /*protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['markets'] = $this->record->markets->map(fn (Markets $market) => [
            'design' => $market->design,
            'market' => $market->market,
            'value' => 321,
        ]);

        return $data;
    }*/

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
