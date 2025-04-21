<?php namespace App\Filament\Resources\Dev\Traits;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

trait Many
{
    private static function many(Column $column, string $model, string $field): Column
    {
        return $column->getStateUsing(fn (Model $query) => $query->{$model}->pluck($field)->join(', '))
            ->tooltip(fn(TextColumn $column) => call_user_func(fn($state) => strlen($state) > $column->getCharacterLimit() ? $state : null, $column->getState()))
            ->size(TextColumn\TextColumnSize::ExtraSmall)
            ->placeholder('NULL')
            ->limit(100)
            ->wrap();
    }
}
