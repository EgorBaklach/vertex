<?php namespace App\Filament\Pages;

use App\Filament\Forms\Components\ImageUpload;
use App\Helpers\Arr;
use App\Models\Sora\Pictures;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\StaticAction;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Filament\Forms\Components\{Select, Textarea, ToggleButtons, Checkbox, Grid, Group, Hidden, TextInput, View};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class Sora extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static string $view = 'pages.sora-editor';

    protected $listeners = [
        'clearGen' => 'clear'
    ];

    public ?array $data = [];

    public int $last_position = 100;

    private const prompt = 'Create a highly original and artistically reimagined version of the source image, using it only as vague inspiration. Feel free to change not only the visual composition but also reinterpret or transform the original idea, tone, or concept into something new, symbolic, surreal, abstract, or metaphorical. Alter characters, poses, environment, atmosphere, and structure freely. The result must not resemble the original in composition, detail, or direct visual elements. You may introduce entirely new stylistic choices, visual metaphors, or even shift the emotional narrative. You are allowed to change the color scheme, lighting, and visual style completely. If the original image contains visible text in English or Russian, preserve that exact text — same wording, same language — but you may change its design, style, or placement to match the new composition. Do not add any new text if none existed. Ensure the final result is a distinct, legally and creatively independent artwork with no direct visual or thematic dependency on the original, aside from inspiration.';

    public function mount(): void
    {
        $this->loadNextRecord();
    }

    public function reload(): void
    {
        $this->loadNextRecord();
    }

    protected function loadNextRecord(): void
    {
        if($record = Pictures::query()->whereNotNull('position')->whereNull('abort')->whereNull('selectGen')->orderBy('priority')->orderBy('position')->orderBy('id')->first())
        {
            foreach(Storage::disk('public')->allFiles('sora/'.$record->number) as $path) if(preg_match('/([^\/]+)\./i', $path, $match)) $record->{$match[1]} = 'https://lv.vertex.ru/storage/'.$path;

            $record->original = 'https://img.vertex.ru/sora/'.$record->number.'.jpg'; $record->design_name = $record->design->name; $this->last_position = (Pictures::max('position') ?? 1) + 1; $record->position = 1;

            ['width' => $width, 'height' => $height] = json_decode($record->parameters, true, 512, JSON_BIGINT_AS_STRING);

            $record->sizes = match($width <=> $height)
            {
                1 => 350,
                0 => 450,
                -1 => 692
            };
        }

        $this->form->fill($record ? $record->toArray() : []);
    }

    public function form(Form $form): Form
    {
        $toggle = ToggleButtons::make('selectGen')->reactive()->afterStateUpdated(fn($state) => $this->mountAction('save', ['Выбран Gen '.$state]))->hiddenLabel();

        return $form
            ->columns(12)
            ->statePath('data')
            ->schema(fn() => !count($this->data) ? [] : [
                ...Arr::map(range(1, 4), fn($n) => Group::make()->columnSpan(3)->schema([
                    ImageUpload::make($this->data['number'].'_'.$n)->imagePreviewHeight($this->data['sizes'])->label('Gen '.$n),
                    clone $toggle->options([$n => 'Выбрать Gen '.$n])
                ])),
                ImageUpload::make('original')->columnSpan(8)->imagePreviewHeight(550),
                Group::make()->columnSpan(4)->schema([
                    TextInput::make('design_name')->disabled(),
                    Group::make()->columns(10)->schema([
                        TextInput::make('number')->columnSpan(7)->disabled(),
                        Select::make('position')->columnSpan(3)->reactive()->native(false)->options([1 => 'active', $this->last_position => 'skip'])->afterStateUpdated(fn($state) => $state * 1 === 1 ?: $this->mountAction('save', match($state*1)
                        {
                            $this->last_position => ['Пропустить выбор?'], default => ['Отправить на перегенерацию?']
                        })),
                    ]),
                    Checkbox::make('svg')->formatStateUsing(fn($state) => $state === 'Y' ? true : null)->label('SVG приемлемо'),
                    View::make('components.sora-legend')->viewData([
                        'legend' => [
                            ["Горячие клавиши:", false],
                            ["------------------", false],
                            ['1,2,3,4', 'Выбор генерации'],
                            ['s', 'SVG приемлемо'],
                            ["------------------", false],
                            ['x', 'Отправить на перегенерацию'],
                            ['b', 'Заблокировать генерацию'],
                            [' ', 'Пропустить выбор']
                        ]
                    ]),
                    Hidden::make('abort'),
                    Hidden::make('id'),
                ])
            ]);
    }

    public function save(?array $input = null): void
    {
        $data = $this->form->getState() + ['date_update' => date('Y-m-d H:i:s'), 'uid' => Auth::id()];

        foreach($input ?? [] as $field => $value) $data[$field] = $value;

        if($data['selectGen'])
        {
            App::make(ImageManagerInterface::class)
                ->read(Storage::disk('public')->path('sora/'.$this->data['number'].'/'.$this->data['number'].'_'.$data['selectGen'].'.webp'))
                ->toJpg(95)->save('/pool3_mockups/sora_out/'.$this->data['number'].'.jpg');
        }

        Pictures::query()->upsert($data, []); $this->loadNextRecord();
    }

    ///////////////
    /// ACTIONS ///
    ///////////////

    public function toggle(string $key): void
    {
        if(str_contains('1234', $key))
        {
            $this->data['selectGen'] = $key * 1; $this->mountAction('save', ['Выбран Gen '.$key]); return;
        }

        if($key === 's')
        {
            $this->data['svg'] = !$this->data['svg']; return;
        }

        if($key === 'b') $this->data['abort'] = 'Y';

        $this->data['position'] = match($key)
        {
            ' ' => $this->last_position, 'b', 'x' => null
        };

        $this->mountAction('save', match($key)
        {
            ' ' => ['Пропустить выбор?'],
            'b' => ['Заблокировать генерацию?'],
            'x' => ['Отправить на перегенерацию?']
        });
    }

    public function clear(): void
    {
        $this->data['selectGen'] = null; $this->data['position'] = 1; $this->data['abort'] = null; $this->mountedActionsArguments = [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->requiresConfirmation()
                ->hidden(fn() => !count($this->data))
                ->form(fn() => is_null($this->data['position']) && is_null($this->data['abort']) ? [Textarea::make('prompt')->rows(14)->default(self::prompt)->label('Новый текст запроса:')] : [])
                ->modalCancelAction(fn(StaticAction $action) => $action->keyBindings(['Esc'])->dispatch('clearGen'))
                ->modalSubmitAction(fn(StaticAction $action) => $action->keyBindings(['Enter']))
                ->modalDescription(fn(array $arguments) => $arguments[0] ?? __('filament-actions::modal.confirmation'))
                ->modalHeading('Подтвердите действие')
                ->closeModalByClickingAway(false)
                ->modalCloseButton(false)
                ->action(fn($data) => $this->save($data))
                ->label('Сохранить')
        ];
    }
}
