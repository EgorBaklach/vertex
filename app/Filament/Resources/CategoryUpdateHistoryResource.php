<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CategoryUpdateHistoryResource\Pages;
use App\Models\CategoryUpdateHistory;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;

class CategoryUpdateHistoryResource extends Resource
{
    protected static ?string $model = CategoryUpdateHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'WB';
    protected static ?string $label = 'История изменений';
    protected static ?string $pluralLabel = 'История изменений категорий';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(20)
            ->paginationPageOptions([20,50,100,'all'])
            ->columns([
                Tables\Columns\TextColumn::make('category.parent_name')->label('Родительская категория')->searchable(),
                Tables\Columns\TextColumn::make('category.subject_name')->label('Подкатегория')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Дата изменения')->sortable(),
            ])
            ->filters([])
            ->actions([
                Action::make('viewDetails')
                    ->label('Просмотр')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Детали записи')
                    ->modalButton('Закрыть')
                    ->modalWidth('xl') // Широкое модальное окно
                    ->modalContent(function ($record) {
                        return view('components.json-viewer', [
                            'oldData' => $record->old_data,
                            'newData' => $record->new_data,
                        ]);
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoryUpdateHistories::route('/'),
        ];
    }
}
