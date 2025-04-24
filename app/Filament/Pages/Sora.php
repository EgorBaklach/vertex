<?php namespace App\Filament\Pages;

use App\Filament\Forms\Components\ImageUpload;
use App\Helpers\Arr;
use App\Models\Sora\Pictures;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\{ToggleButtons, Checkbox, Grid, Group, Hidden, TextInput};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

/**
 * @property Form $form
 */
class Sora extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static string $view = 'pages.sora-editor';

    private ToggleButtons $toggle;

    protected $listeners = [
        'clearGen' => 'clear'
    ];

    public ?array $data = [];

    private const gens = [[1, 2], [3, 4]];

    public function mount(): void
    {
        $this->loadNextRecord();
    }

    protected function loadNextRecord(): void
    {
        $this->form->fill(Pictures::query()->where('active', 'Y')->whereNull('selectGen')->first()->toArray());
    }

    private function photo(string $field, string $name, int $height, ?int $gen = null): ImageUpload
    {
        return ImageUpload::make($field)
            ->afterStateHydrated(fn(ImageUpload $component) => $component->state(['sora/'.$this->data['article'].'/'.$this->data['article'].($gen ? '_'.$gen : '').'.'.($gen ? 'webp' : 'jpg')]))
            ->imagePreviewHeight($height)
            ->label($name);
    }

    public function form(Form $form): Form
    {
        $toggle = ToggleButtons::make('selectGen')->reactive()->afterStateUpdated(fn() => $this->mountAction('save'))->hiddenLabel();

        return $form
            ->columns(12)
            ->statePath('data')
            ->schema([
                Group::make()
                    ->columnSpan(4)
                    ->schema([
                        $this->photo('original', 'Основное изображение', 817),
                        TextInput::make('article')->label('Артикул')->disabled()->hiddenLabel(),
                        Checkbox::make('svg')->formatStateUsing(fn($state) => $state === 'Y')->label('SVG приемлемо'),
                        Hidden::make('id')
                    ]),
                Group::make()
                    ->columnSpan(8)
                    ->schema(Arr::map(self::gens, fn($r) => Grid::make()->schema(Arr::map($r, fn($v) => Group::make()->schema([
                        $this->photo('gen_'.$v, 'Gen '.$v, 350, $v),
                        clone $toggle->options([$v => 'Выбрать Gen '.$v])
                    ])))))
            ]);
    }

    public function save(): void
    {
        Log::channel('design')->info($this->form->getState());

        /*if (!$this->currentRecord) {
            Notification::make()
                ->danger()
                ->title('Нет записей для редактирования!')
                ->send();
            return;
        }

        $this->currentRecord->update($this->form->getState());

        Notification::make()
            ->success()
            ->title('Сохранено!')
            ->send();

        $this->loadNextRecord(); // Загружаем следующую запись

        if (!$this->currentRecord) {
            Notification::make()
                ->success()
                ->title('Все записи обработаны!')
                ->send();
        }*/
    }

    public function toggle(string $key): void
    {
        if(str_contains('1234', $key))
        {
            $this->data['selectGen'] = $key * 1; $this->mountAction('save');
        }

        if($key === 's') $this->data['svg'] = !$this->data['svg'];
    }

    public function clear(): void
    {
        $this->data['selectGen'] = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->modalCancelAction(fn(StaticAction $action) => $action->keyBindings(['Esc'])->dispatch('clearGen'))
                ->modalSubmitAction(fn(StaticAction $action) => $action->keyBindings(['Enter']))
                ->modalHeading('Подтвердите действие')
                ->action(fn() => $this->save())
                ->requiresConfirmation()
                ->label('Сохранить')
        ];
    }
}
