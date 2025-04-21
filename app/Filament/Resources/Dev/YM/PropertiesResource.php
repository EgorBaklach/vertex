<?php namespace App\Filament\Resources\Dev\YM;

use App\Filament\Clusters\Dev;
use App\Helpers\Arr;
use App\Models\Dev\YM\CP;
use App\Models\Dev\YM\PU;
use App\Models\Dev\YM\Properties;
use App\Models\Dev\YM\PV;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PropertiesResource extends Resource
{
    protected static ?string $model = Properties::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Характеристика';
    protected static ?string $pluralModelLabel = 'Характеристики';

    protected static ?string $navigationLabel = 'Характеристики';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'YM';

    private const limit = 50;

    public static function table(Table $table): Table
    {
        return $table->defaultPaginationPageOption(25)->paginationPageOptions([25, 50, 100, 250])->columns([
            TextColumn::make('ids')->verticalAlignment(VerticalAlignment::Start)
                ->extraAttributes(['class' => 'list-padding-quarter'])
                ->state(fn (Properties $property): array => [
                    TextColumn::make('id')->record($property)->description('ID:', 'above'),
                    TextColumn::make('last_request')->record($property)->description('Дата импорта:', 'above'),
                    TextColumn::make('type')->record($property)->description('Тип:', 'above')
                ])
                ->listWithLineBreaks()
                ->wrap()
                ->label('Идентификатор и тип'),
            TextColumn::make('active')->verticalAlignment(VerticalAlignment::Start)->formatStateUsing(fn($state) => match($state){'Y' => 'Включен', 'D' => 'В ожидании'})->placeholder('Отключен')->label('Активность'),
            TextColumn::make('info')->verticalAlignment(VerticalAlignment::Start)
                ->getStateUsing(function(Properties $property)
                {
                    if(!$property->cp->count()) return null; /** @var CP $cp */ $rows = [];

                    foreach($property->cp->all() as $cp)
                    {
                        $rows[$hash = md5($name = trim($cp->name))] ??= [
                            'name' => $name,
                            'description' => trim($cp->description),
                            'restrictions' => implode("<br>", array_filter([
                                $cp->constMaxLength ? '<b>Макс колво симв</b>: ' . $cp->constMaxLength : null,
                                $cp->constMinValue ? '<b>Мин число</b>: ' . $cp->constMinValue : null,
                                $cp->constMaxValue ? '<b>Макс число</b>: ' . $cp->constMaxValue : null,
                            ])),
                            'values' => [],
                            'cnt' => 0
                        ];

                        if(count($rows[$hash]['values']) <= self::limit) $rows[$hash]['values'][] = $cp->category->name; $rows[$hash]['cnt']++;
                    }

                    return new HtmlString(implode("<br><br>------------------------------------------<br><br>", Arr::map($rows, fn($v) => implode('', array_filter([
                        '<b>'.$v['name'].'</b>'.($v['cnt'] > self::limit ? ' ('.$v['cnt'].')' : ''),
                        strlen($v['description']) ? '<br><br><i>'.$v['description'].'</i><br><br><b>Категории</b>' : null,
                        ': '.implode(', ', $v['values']),
                        strlen($v['restrictions']) ? '<br><br>'.$v['restrictions'] : null,
                    ])))));
                })
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->placeholder('NULL')
                ->wrap()
                ->label('Название / категории'),
            TextColumn::make('dimensions')->verticalAlignment(VerticalAlignment::Start)
                ->getStateUsing(fn(Properties $property) => !$property->pu->count() ? null : new HtmlString($property->pu->map(fn(PU $pu) => '<b>'.$pu->unit->fullName.'</b>: ('.$pu->unit->name.')'.($pu->def === 'Y' ? ' def' : ''))->join('<br>')))
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->placeholder('NULL')
                ->label('Ед изм'),
            TextColumn::make('additions')->verticalAlignment(VerticalAlignment::Start)
                ->getStateUsing(fn(Properties $property) => call_user_func(fn(...$props) => !count($props) ? null : new HtmlString(implode('<br>', $props)), ...array_filter([
                    $property->required === 'Y' ? '* Обязательно к заполнению' : null,
                    $property->filtering === 'Y' ? '* Используетcя в фильтре' : null,
                    $property->distinctive === 'Y' ? '* Зависимость от выбора' : null,
                    $property->multivalue === 'Y' ? '* Можно выбрать несколько значений' : null,
                    $property->allowCustomValues === 'Y' ? '* Можно передать свое значение' : null,
                    $property->hasValues === 'Y' ? '* Есть выбор значений' : null,
                    $property->hasValueRestrictions === 'Y' ? '* Есть ограничения выбора' : null
                ])))
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->placeholder('NULL')
                ->label('Дополнительные данные'),
            TextColumn::make('values')->verticalAlignment(VerticalAlignment::Start)
                ->getStateUsing(function(Properties $property)
                {
                    if(!$cnt = PV::query()->where('pid', $property->id)->count()) return null; $rows = [];

                    foreach($property->pv(self::limit)->distinct('value')->pluck('value') as $value) $rows[] = $value;

                    return new HtmlString(($cnt > count($rows) ? '<b>Всего</b>: '.$cnt.'<br><br>' : '').implode(', ', $rows));
                })
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->placeholder('NULL')
                ->wrap()
                ->label('Значения характеристики')
        ])->filters([
            SelectFilter::make('active')->default('Y')
                ->options(['Y' => 'Включен', 'D' => 'В ожидании', 'NULL' => 'На удаление'])
                ->query(fn(Builder $query, $data) => !$data['value'] ? null : ($data['value'] !== 'NULL' ? $query->where('active', $data['value']) : $query->whereNull('active')))
                ->label('Активность'),
            Filter::make('id')
                ->query(fn(Builder $query, $data) => $query->when($data['id'], fn(Builder $query, $id) => call_user_func(fn(...$ids) => count($ids) > 1 ? $query->whereIn('id', $ids) : $query->whereLike('id', current($ids).'%'), ...explode(',', $id))))
                ->form([TextInput::make('id')->label('ID характеристики')]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListProperties::route('/')];
    }
}
