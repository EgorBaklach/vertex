<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Задача для fetch:wb-categories
Artisan::command('schedule:fetch-wb-categories', function () {
    $this->call('fetch:wb-categories');
})->purpose('Fetch categories from Wildberries API')->dailyAt('20:50');

Artisan::command('schedule:fetch-wb-orders', fn() => $this->call('fetch:wb-orders'))->purpose('Fetch orders from Wildberries API')->everyFiveMinutes();

################ ЕГОР

// Запуск чтения расписания операций
#Artisan::command('pending', fn() => $this->call('schedule:pending'))->everyMinute();

// Проверка на размер Логов - удалить если больше 100МБ
Artisan::command('checking:logSize', fn() => $this->call('check:logSize'))->everyTenMinutes();
