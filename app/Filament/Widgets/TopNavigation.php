<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TopNavigation extends Widget
{
    protected static string $view = 'widgets.top-navigation';

    protected static ?int $sort = -1; // Устанавливает виджет в самом верху

    public static function canView(): bool
    {
        // Делаем виджет видимым на всех страницах
        return true;
    }
}
