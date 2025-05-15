<?php namespace App\Filament\Pages;

use App\Filament\Forms\Components\ImageUpload;
use App\Helpers\Arr;
use App\Models\Sora\Pictures;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\StaticAction;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\{Select, Textarea, ToggleButtons, Checkbox, Grid, Group, Hidden, TextInput};
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

    private const gens = [[1, 2], [3, 4]];

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
        if($record = Pictures::query()->whereNotNull('status')->whereNull('selectGen')->orderBy('status')->orderBy('id')->first())
        {
            foreach(Storage::disk('public')->allFiles('sora/'.$record->number) as $path) if(preg_match('/([^\/]+)\./i', $path, $match)) $record->{$match[1]} = 'https://lv.vertex.ru/storage/'.$path;

            $record->original = 'https://img.vertex.ru/sora/'.$record->number.'.jpg';
            $record->design_name = $record->design->name;
        }

        $this->form->fill($record ? $record->toArray() : []);
    }

    public function form(Form $form): Form
    {
        $toggle = ToggleButtons::make('selectGen')->reactive()->afterStateUpdated(fn($state) => $this->mountAction('save', ['Выбран Gen '.$state]))->hiddenLabel();

        return $form
            ->columns(12)
            ->statePath('data')
            ->schema(fn() => count($this->data) ? [
                Group::make()
                    ->columnSpan(4)
                    ->schema([
                        ImageUpload::make('original')->imagePreviewHeight(817)->label('Основное изображение'),
                        TextInput::make('design_name')->disabled()->hiddenLabel(),
                        Hidden::make('id'),
                        Group::make()
                            ->columns(10)
                            ->schema([
                                TextInput::make('number')->columnSpan(4)->disabled()->hiddenLabel(),
                                Select::make('status')->columnSpan(3)->reactive()->native(false)->options([1 => 'active', 2 => 'skip'])->hiddenLabel()->afterStateUpdated(fn($state) => $state * 1 === 1 ?: $this->mountAction('save', match($state*1)
                                {
                                    2 => ['Пропустить выбор?'], default => ['Отправить на перегенерацию?']
                                })),
                                Checkbox::make('svg')->columnSpan(3)->formatStateUsing(fn($state) => $state === 'Y' ? true : null)->label('SVG приемлемо')
                            ])
                    ]),
                Group::make()
                    ->columnSpan(8)
                    ->schema(Arr::map(self::gens, fn($r) => Grid::make()->schema(Arr::map($r, fn($v) => Group::make()->schema([
                        ImageUpload::make($this->data['number'].'_'.$v)->imagePreviewHeight(350)->label('Gen '.$v),
                        clone $toggle->options([$v => 'Выбрать Gen '.$v])
                    ])))))
            ] : []);
    }

    public function save(?array $input = null): void
    {
        $data = $this->form->getState(); foreach($input ?? [] as $field => $value) $data[$field] = $value; Pictures::query()->upsert($data, []); $this->loadNextRecord();
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

        $this->data['status'] = match($key)
        {
            ' ' => 2,
            'x' => null
        };

        $this->mountAction('save', match($key)
        {
            ' ' => ['Пропустить выбор?'],
            'x' => ['Отправить на перегенерацию?']
        });
    }

    public function clear(): void
    {
        $this->data['selectGen'] = null; $this->data['status'] = 1; $this->mountedActionsArguments = [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->requiresConfirmation()
                ->hidden(fn() => !count($this->data))
                ->form(fn() => is_null($this->data['status']) ? [Textarea::make('prompt')->rows(14)->default(self::prompt)->label('Новый текст запроса:')] : [])
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
