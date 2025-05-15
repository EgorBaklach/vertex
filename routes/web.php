<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\OrderController;

Route::get('/', function () {
    return view('welcome');
});

// Универсальный маршрут для сканирования (GET и POST)
Route::match(['GET', 'POST'], '/scan', [ScanController::class, 'handleScan'])->name('scan.handle');

// Маршрут для сохранения выбранного принтера
Route::post('/save-selected-printer', [ScanController::class, 'saveSelectedPrinter'])->name('save.selected.printer');

// Маршрут для печати
Route::post('/print', [ScanController::class, 'print'])->name('print');

Route::post('/filament/resources/wb-orders/load-orders', function () {
    Artisan::call('fetch:wb-orders');
    return redirect()->back()->with('success', 'Заказы успешно загружены.');
})->name('filament.resources.wb-orders.loadOrders');

Route::match(['GET', 'POST'], '/orders', [OrderController::class, 'showSearchPage'])->name('orders.search');

Route::get('/orders', function () {
    return view('orders.search');
})->name('orders.search.page');
