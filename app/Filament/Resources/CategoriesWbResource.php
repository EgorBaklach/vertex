<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriesWbResource\Pages;
use App\Models\CategoryWb;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Artisan;

class CategoriesWbResource extends Resource
{
    protected static ?string $model = CategoryWb::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'WB';
    protected static ?string $label = 'Комиссии Wildberries';
    protected static ?string $pluralLabel = 'Комиссии Wildberries';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('parent_id')
                ->label('ID Родительской категории')
                ->disabled(),
            Forms\Components\TextInput::make('parent_name')
                ->label('Название родительской категории')
                ->disabled(),
            Forms\Components\TextInput::make('subject_id')
                ->label('ID Подкатегории')
                ->disabled(),
            Forms\Components\TextInput::make('subject_name')
                ->label('Название подкатегории')
                ->disabled(),
            Forms\Components\TextInput::make('kgvp_marketplace')
                ->label('FBS, %')
                ->disabled(),
            Forms\Components\TextInput::make('kgvp_supplier')
                ->label('Real FBS, %')
                ->disabled(),
            Forms\Components\TextInput::make('kgvp_supplier_express')
                ->label('Real FBS Express, %')
                ->disabled(),
            Forms\Components\TextInput::make('paid_storage_kgvp')
                ->label('FBW, %')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('parent_name')
                    ->label('Родительская Категория')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_name')
                    ->label('Подкатегория')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('kgvp_marketplace')
                    ->label('FBS, %')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kgvp_supplier')
                    ->label('Real FBS, %')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kgvp_supplier_express')
                    ->label('Real FBS Express, %')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_storage_kgvp')
                    ->label('FBW, %')
                    ->sortable(),
            ])
            ->filters([
                // Фильтры, если нужны
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->headerActions([
                Action::make('load_categories')
                    ->label('Загрузить категории вручную')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        try {
                            // Вызов Artisan-команды
                            Artisan::call('fetch:wb-categories'); $output = Artisan::output();

                            // Уведомление о успешной загрузке
                            Notification::make()
                                ->title('Успех')
                                ->success()
                                ->body("Категории успешно загружены!<br><pre>{$output}</pre>")
                                ->send();
                        } catch (\Exception $e) {
                            // Уведомление об ошибке
                            Notification::make()
                                ->title('Ошибка')
                                ->danger()
                                ->body("Ошибка при загрузке категорий: {$e->getMessage()}")
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->color('success'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoriesWbs::route('/'),
            'edit' => Pages\EditCategoriesWb::route('/{record}/edit'),
        ];
    }
}
