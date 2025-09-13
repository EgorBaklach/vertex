<?php namespace App\Filament\Resources\Dev\Traits;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

trait ParamsEdit
{
    private static function params(array $data): array
    {
        return array_merge($data, ['params' => match($data['marketplace'])
        {
            'OZON' => [
                'Client-Id' => $data['Params-Client-Id']
            ],
            'YM' => [
                'domain' => $data['Params-domain'],
                'id' => $data['Params-id'],
                'clientId' => $data['Params-clientId'],
                'business' => [
                    'id' => $data['Params-businessId'],
                    'name' => $data['Params-businessName'],
                ],
                'placementType' => $data['Params-placementType']
            ],
            default => null
        }]);
    }
}
