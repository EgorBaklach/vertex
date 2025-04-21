<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сканирование и печать</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Сканирование QR-кодов и печать</h1>

        <!-- Ошибки -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Форма -->
        <form id="scan-form" action="{{ route('scan.handle') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="scan_data" class="form-label">Введите или сканируйте QR-код</label>
                <input type="text" class="form-control" id="scan_data" name="scan_data" placeholder="Штрихкод.НомерЗаказа" required autofocus>
            </div>

            <!-- Выбор принтера -->
            <div class="mb-3">
                <label for="printer" class="form-label">Выберите принтер</label>
                <select id="printer" name="printer" class="form-select" required>
                    @foreach ($printers as $printer)
                        <option value="{{ $printer->id }}" {{ $printer->id == $selectedPrinter ? 'selected' : '' }}>
                            {{ $printer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить и вывести на печать</button>
        </form>

        <!-- Информация о товаре -->
        @if ($productInfo)
            <div class="mt-5">
                <h2>Информация о товаре</h2>
                <div class="card">
                    <div class="card-body">
                        <p><strong>Наименование:</strong> {{ $productInfo['name'] }}</p>
                        <p><strong>Характеристика:</strong> {{ $productInfo['characteristic'] }}</p>
                        <p><strong>Размер:</strong> {{ $productInfo['size'] }}</p>
                    </div>
                </div>
                <div id="product-photo" class="border bg-light mt-3" style="width: 100%; height: 200px;">
                    <!-- Фото товара будет добавлено позже -->
                    <p class="text-center mt-5">Фото товара</p>
                </div>
            </div>
        @endif

        <!-- Результат JSON -->
        @if ($data)
            <div class="mt-5">
                <h2>Информация о штрихкоде</h2>
                <pre class="bg-light p-3 rounded">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const printerSelect = document.getElementById('printer');
            const scanForm = document.getElementById('scan-form');

            // Сохраняем выбранный принтер на сервер при изменении
            printerSelect.addEventListener('change', () => {
                const selectedPrinterId = printerSelect.value;

                fetch('{{ route('save.selected.printer') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ printer_id: selectedPrinterId }),
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Принтер сохранён:', data.message);
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                });
            });

            // Восстанавливаем фокус после отправки формы
            scanForm.addEventListener('submit', () => {
                setTimeout(() => {
                    document.getElementById('scan_data').value = ''; // Очищаем поле
                    document.getElementById('scan_data').focus(); // Восстанавливаем фокус
                }, 10);
            });
        });
    </script>
</body>
</html>
