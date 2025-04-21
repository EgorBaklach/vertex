<?php namespace App\Filament\Resources\Management\Design;

use App\Filament\Clusters\Management;
use App\Filament\Resources\Management\Design\Pages\ListPatterns;
use App\Filament\Resources\PatternResource\PatternAbstract;
use App\Models\Management\Designs\Patterns;
use Illuminate\Support\Facades\Schema;

class PatternsResource extends PatternAbstract
{
    protected static ?string $model = Patterns::class;
    protected static ?string $cluster = Management::class;

    protected static ?string $navigationGroup = 'Designs';

    protected const list = ListPatterns::class;

    private const relations = [
        'colors' => ['market', 'value', 'цвет для'],
        'groups' => ['point', 'value', 'группа']
    ];

    protected static function getFields(): array
    {
        $fields = []; $connection = Schema::connection('management');

        foreach($connection->getColumns('designs') as $field) $fields['fields'][$field['name']] = $field['name'].' - '.$field['comment'];

        foreach(self::relations as $table => [$column, $value, $description])
        {
            preg_match('/enum\((.*)\)$/', $connection->getColumnType($table, $column, true), $matches);

            foreach(explode(',', str_replace("'", "", $matches[1])) as $variant) $fields[$table][$table.'.'.$column.'.'.$variant.'.'.$value] = $variant.' - '.$description.' '.$variant;
        }

        return $fields;
    }
}
