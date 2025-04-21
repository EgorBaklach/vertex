<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск заказа</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Поиск заказа</h1>

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
        <form action="{{ route('orders.search') }}" method="POST" class="mb-5">
            @csrf
            <div class="mb-3">
                <label for="barcode" class="form-label">Введите или сканируйте штрихкод</label>
                <input type="text" class="form-control" id="barcode" name="barcode" placeholder="Штрихкод" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary">Поиск</button>
        </form>

        <!-- Информация о заказах -->
        @isset($orderInfo)
            <div class="card">
                <div class="card-header">
                    <strong>Результаты поиска для штрихкода: {{ $orderInfo['barcode'] }}</strong>
                </div>
                <div class="card-body">
                    @if ($orderInfo['orders']->isEmpty())
                        <p>Заказы не найдены.</p>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID заказа</th>
                                    <th>Артикул</th>
                                    <th>Название токена</th>
                                    <th>Дата создания</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orderInfo['orders'] as $order)
                                    <tr>
                                        <td>{{ $order['id'] }}</td>
                                        <td>{{ $order['article'] }}</td>
                                        <td>{{ $order['token_name'] }}</td>
                                        <td>{{ $order['created_at'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endisset
    </div>
</body>
</html>
