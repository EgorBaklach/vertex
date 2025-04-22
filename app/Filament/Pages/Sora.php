<?php

namespace App\Filament\Pages;

use App\Models\Sora\Pictures;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
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

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static string $view = 'pages.sora-editor';

    public ?array $data = [];

    public function mount(): void
    {
        $this->loadNextRecord();
    }

    protected function loadNextRecord(): void
    {
        $this->form->fill(Pictures::query()->where('active', 'Y')->whereNull('selectGen')->first()->toArray());
    }

    private function photo(FileUpload $upload, ?int $gen = null): FileUpload
    {
        return $upload
            ->afterStateHydrated(fn(FileUpload $component) => $component->state(['sora/'.$this->data['article'].'/'.$this->data['article'].($gen ? '_'.$gen : '').'.'.($gen ? 'webp' : 'jpg')]))
            ->multiple(false)
            ->previewable()
            ->disabled()
            ->openable()
            ->image();
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->statePath('data')
            ->schema([
                Group::make()
                    ->columnSpan(4)
                    ->schema([
                        $this->photo(FileUpload::make('original')->imagePreviewHeight(756)->label('Основное изображение')),
                        TextInput::make('article')->label('Артикул')->disabled(),
                        Checkbox::make('svg_acceptable')->label('SVG приемлемо'),
                        Hidden::make('id')
                    ]),
                Group::make()
                    ->columnSpan(8)
                    ->schema([
                        Grid::make()
                            ->schema([
                                $this->photo(FileUpload::make('gen_1')->imagePreviewHeight(350)->label('Gen 1'), 1),
                                $this->photo(FileUpload::make('gen_2')->imagePreviewHeight(350)->label('Gen 2'), 2)
                            ]),
                        Grid::make()
                            ->schema([
                                $this->photo(FileUpload::make('gen_3')->imagePreviewHeight(350)->label('Gen 3'), 3),
                                $this->photo(FileUpload::make('gen_4')->imagePreviewHeight(350)->label('Gen 4'), 4)
                            ]),
                    ])
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

    protected function getSaveFormAction(): array
    {
        return [
            Action::make('save')->submit('save')->label('Save')
        ];
    }
}
