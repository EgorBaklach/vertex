<?php namespace App\Filament\Pages;

use App\Filament\Clusters\Dev;
use App\Helpers\Func;
use App\Helpers\Time;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Schedule;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

/**
 * @property Form $form
 */
class MarketManualImport extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $cluster = Dev::class;

    protected static ?string $navigationLabel = 'Запуск импорта вручную';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'GENERAL';

    protected static ?string $title = 'Запуск импорта вручную';

    protected static string $view = 'pages.market-manual-import';

    private array $markets = [
        'WB' => [
            'sequence' => ['PRODUCTS', 'FBS_STOCKS', 'PRICES', 'WB_PRICES'],
            'label' => 'Wildberries'
        ],
        'OZON' => [
            'sequence' => ['PRODUCTS', 'PRICES', 'FBS_STOCKS'],
            'label' => 'OZON'
        ],
        'YM' => [
            'sequence' => ['PRODUCTS', 'STOCKS'],
            'label' => 'Yandex Market'
        ]
    ];

    public ?array $tokens = [];

    public ?array $stats = [];

    public ?array $data = [];

    public const operations = [
        'products' => [
            'label' => 'Импорт товаров',
            'marketplaces' => [
                'WB' => 'PRODUCTS',
                'OZON' => 'PRODUCTS',
                'YM' => 'PRODUCTS'
            ]
        ],
        'prices' => [
            'label' => 'Импорт цен',
            'marketplaces' => [
                'WB' => 'PRICES',
                'OZON' => 'PRICES'
            ]
        ],
        'stocks' => [
            'label' => 'Импорт остатков FBS',
            'marketplaces' => [
                'WB' => 'FBS_STOCKS',
                'OZON' => 'FBS_STOCKS',
                'YM' => 'STOCKS'
            ]
        ]
    ];

    public function getBreadcrumbs(): array
    {
        return parent::getBreadcrumbs() + ['Запуск импорта вручную'];
    }

    public function mount(): void
    {
        $this->form->fill(); foreach(MarketplaceApiKey::query()->where('active', 'Y')->get() as $token) /** @var MarketplaceApiKey $token */ $this->tokens[$token->marketplace][$token->id] = $token->name; $this->operations();
    }

    public function reload(): void
    {
        $this->operations();
    }

    private function operations(): void
    {
        $this->stats = [];

        foreach(self::operations as $operation => $params)
        {
            foreach($params['marketplaces'] as $marketplace => $native_operation)
            {
                if($task = Cache::get(implode('_', ['MANUAL', 'IMPORT', $marketplace, $native_operation])))
                {
                    $this->stats['queue'][$operation][$marketplace] = (count($task['sequence']) ? 'Queue: '.implode(' -> ', $task['sequence']).' | ' : '').'Tokens: '.implode(', ', Arr::map($task['tokens'], fn($tid) => $this->tokens[$marketplace][$tid]));
                }
            }
        }

        foreach(array_keys($this->markets) as $market) if($runner = Cache::get(implode('_', ['MANUAL', 'IMPORT', $market, 'IS_RUNNING']))) $this->stats['runners'][$market] = $runner['operation'];

        foreach(Schedule::query()->where('active', 'Y')->where('counter', '>', 0)->orderBy('market')->get() as $schedule) /** @var Schedule $schedule */
        {
            $this->stats['processes'][$schedule->market][$schedule->operation] = compact('schedule') + ['label' => $schedule->operation.' - '.Time::during($schedule->start && time() > $schedule->start ? time() - $schedule->start : 0)];
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->statePath('data')
            ->schema(fn() => [
                Select::make('operation')
                    ->columnSpan(6)
                    ->options(array_map(fn($v) => $v['label'], self::operations))
                    ->reactive()
                    ->required()
                    ->native(false)
                    ->searchable(false)
                    ->placeholder('Выберите операцию импорта')
                    ->afterStateUpdated(fn (Set $set) => [$set('marketplace', null), $set('tokens', [])]),
                Select::make('marketplace')
                    ->columnSpan(6)
                    ->disabled(fn(Get $get) => !count($this->markets($get('operation'))))
                    ->options(fn(Get $get) => $this->markets($get('operation')))
                    ->reactive()
                    ->required()
                    ->native(false)
                    ->searchable(false)
                    ->placeholder('Выберите маркетплейс')
                    ->afterStateUpdated(fn (Set $set) => $set('tokens', [])),
                Select::make('tokens')
                    ->columnSpan(12)
                    ->options(fn(Get $get) => Func::call($get('marketplace'), fn(?string $marketplace) => !$marketplace ? [] : $this->tokens[$marketplace]))
                    ->preload()
                    ->required()
                    ->multiple()
                    ->native(false)
                    ->maxItems(100)
                    ->disabled(fn ($get) => !$get('marketplace'))
                    ->placeholder('Выберите один или несколько токенов')
            ]);
    }

    private function markets(?string $operation): array
    {
        if(!$operation) return []; if($operation === 'prices') $this->markets['YM'] = null;

        foreach($this->stats['queue'] ?? [] as $markets) foreach(array_keys($markets) as $marketplace) if($this->markets[$marketplace] ?? false) $this->markets[$marketplace] = null;

        return array_map(fn($market) => $market['label'], array_filter($this->markets));
    }

    public function submit(): void
    {
        Cache::set($hash = implode('_', ['MANUAL', 'IMPORT', $this->data['marketplace'], self::operations[$this->data['operation']]['marketplaces'][$this->data['marketplace']]]), compact('hash') + [
            'operation' => self::operations[$this->data['operation']]['marketplaces'][$this->data['marketplace']],
            'marketplace' => $this->data['marketplace'],
            'tokens' => $this->data['tokens'],
            'sequence' => $this->sequence()
        ], 86400);

        Notification::make()->title('Запуск импорта')->body(self::operations[$this->data['operation']]['label'].' для '.$this->data['marketplace'].' поставлен в очередь на выполнение')->success()->send();

        $this->form->fill();
    }

    private function sequence(): array
    {
        $sequence = []; if($this->process('OZON', 'PRICES', 'next_start') > strtotime('today 12:00')) $this->markets['OZON']['sequence'] = ['PRODUCTS', 'PRICES'];

        foreach($this->markets[$this->data['marketplace']]['sequence'] as $operation)
        {
            if($this->stats['processes'][$this->data['marketplace']][$operation] ?? false)
            {
                return array_diff($this->markets[$this->data['marketplace']]['sequence'], $sequence);
            }

            $sequence[] = $operation;
        }

        return [];
    }

    /** @return Schedule|null|string */
    private function process(string $marketplace, string $operation, ?string $field): mixed
    {
        if($schedule = $this->stats['processes'][$marketplace][$operation]['schedule'] ?? false) return $field ? $schedule->{$field} : $schedule; return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->action(fn() => $this->submit())
                ->modalDescription(fn() => count($sequence = $this->sequence()) ? new HtmlString('Импорт "'.current($sequence).'" для '.$this->data['marketplace'].' запущен в данный момент.<br>Запустить "'.self::operations[$this->data['operation']]['label'].'" для '.$this->data['marketplace'].' после завершения операций: <i>'.implode(' -> ', $sequence).'</i>?') : null)
                ->disabled(fn() => count(array_diff(['operation', 'marketplace', 'tokens'], array_keys(array_filter($this->data)))))
                ->modalHeading(fn() => count($this->sequence()) ? 'Подтвердите действие' : null)
                ->modalFooterActionsAlignment(Alignment::Center)
                ->modalAlignment(Alignment::Center)
                ->modalWidth(MaxWidth::Medium)
                ->label('Запустить импорт'),
            Action::make('clear')
                ->action(function()
                {
                    foreach(self::operations as $operation) foreach ($operation['marketplaces'] as $marketplace => $native_operation) Cache::delete(implode('_', ['MANUAL', 'IMPORT', $marketplace, $native_operation]));

                    Notification::make()->title('Очередь успешно очищена')->success()->send(); $this->form->fill();
                })
                ->requiresConfirmation()
                ->disabled(fn() => !count($this->stats['queue'] ?? []))
                ->label('Очистить очередь')
        ];
    }
}