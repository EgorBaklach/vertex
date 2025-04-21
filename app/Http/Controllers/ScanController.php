<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CutScan;
use App\Models\Printer;
use Illuminate\Support\Facades\Http;

class ScanController extends Controller
{
    // Основной метод для отображения формы и обработки сканирования
public function handleScan(Request $request)
{
    $data = null;
    $productInfo = null; // Информация о товаре

    if ($request->isMethod('post')) {
        $validated = $request->validate([
            'scan_data' => 'required|string',
            'printer' => 'required|exists:printers,id',
        ]);

        $scanData = explode('.', $validated['scan_data']);

        if (count($scanData) !== 2 || !preg_match('/^\d{13}$/', $scanData[0])) {
            return redirect()->back()->withErrors(['scan_data' => 'Некорректный формат данных. Используйте: Штрихкод.НомерЗаказа']);
        }

        $barcode = $scanData[0];
        $orderNumber = $scanData[1];

        // Замер времени
        $start = microtime(true);

        // Запрос к API
        $response = Http::get("http://192.168.1.210/vertex/hs/mm/getByBarcode", [
            'barcode' => $barcode,
        ]);

        if ($response->status() === 404 && stripos($response->body(), 'product not found') !== false) {
            return redirect()->back()->withErrors(['scan_data' => 'Штрихкод не найден в 1С']);
        } elseif ($response->failed()) {
            return redirect()->back()->withErrors(['scan_data' => 'Не удалось подключиться к API 1С']);
        }

        $data = $response->json();

        // Извлекаем информацию о товаре
        $size = $this->extractSize($data['CharacteristicName'] ?? '');
        $productInfo = [
            'name' => $data['NomenclatureName'] ?? 'Неизвестно',
            'characteristic' => $data['CharacteristicName'] ?? 'Не указано',
            'size' => $size,
        ];

        // Лог времени после ответа API
        \Log::info('API response time: ' . (microtime(true) - $start) . ' seconds');

        // Сохраняем данные в базу
        CutScan::create([
            'barcode' => $barcode,
            'order_number' => $orderNumber,
            'user_id' => auth()->check() ? auth()->id() : null,
            'windows_user' => '',
            'scanned_at' => now(),
        ]);

        // Формирование пути к файлу на основе размера
        $filePath = "/var/www/laravel/public/labels/{$size}.zpl";

        if (file_exists($filePath)) {
            // Лог времени перед печатью
            \Log::info('Time before print: ' . (microtime(true) - $start) . ' seconds');

            // Отправляем на печать
            $this->print(new Request([
                'printer_id' => $validated['printer'],
                'file_path' => $filePath,
            ]));

            // Лог времени после печати
            \Log::info('Total time after print: ' . (microtime(true) - $start) . ' seconds');
        } else {
            // Лог об отсутствии файла
            \Log::warning("Файл для размера {$size} не найден. Печать пропущена.");
        }
    }

    $printers = Printer::all();
    $selectedPrinter = $request->cookie('selected_printer');

    return view('scan.form', compact('data', 'printers', 'selectedPrinter', 'productInfo'));
}

    /**
     * Извлечение размера из характеристики.
     */
    private function extractSize($characteristic)
    {
        $validSizes = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '4XL', '5XL', '6XL',
            '110', '116', '122', '128', '134', '140', '146', '152', '158', '164', '170', '176'];

        if (strpos($characteristic, '_') !== false) {
            $parts = explode('_', $characteristic);
            foreach ($parts as $part) {
                if (in_array($part, $validSizes, true)) {
                    return $part;
                }
            }
        }

        return 'Не указан';
    }

    // Метод для сохранения выбранного принтера (AJAX)
    public function saveSelectedPrinter(Request $request)
    {
        $validated = $request->validate([
            'printer_id' => 'required|exists:printers,id',
        ]);

        // Сохраняем выбранный принтер в куки
        cookie()->queue('selected_printer', $validated['printer_id'], 525600); // 1 год

        return response()->json(['message' => 'Принтер сохранён']);
    }

    // Метод для печати на выбранном принтере
    public function print(Request $request)
    {
        $validated = $request->validate([
            'printer_id' => 'required|exists:printers,id',
            'file_path' => 'required|string', // Убираем тип "file"
        ]);

        $printer = Printer::findOrFail($validated['printer_id']);
        $filePath = $validated['file_path'];

        // Проверяем, существует ли файл
        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Файл для печати не найден.'], 404);
        }

        $printerName = escapeshellarg($printer->name);
        $escapedFilePath = escapeshellarg($filePath);

        // Выполняем команду печати
        $command = "lp -d {$printerName} -o raw {$escapedFilePath}";
        \Log::info("Print command: {$command}");

        exec($command, $output, $resultCode);

        if ($resultCode === 0) {
            return response()->json(['message' => 'Печать выполнена успешно.']);
        } else {
            \Log::error("Print failed: " . implode("\n", $output));
            return response()->json(['message' => 'Ошибка печати.', 'output' => $output], 500);
        }
    }
}
