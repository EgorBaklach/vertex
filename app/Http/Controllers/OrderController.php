<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WbOrder;

class OrderController extends Controller
{
    public function showSearchPage(Request $request)
    {
        $orderInfo = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'barcode' => 'required|string',
            ]);

            $barcode = $request->input('barcode');

            // Поиск заказов с учетом статуса "new" и штрихкода
            $orders = WbOrder::where('supplier_status', 'new')
                ->whereJsonContains('skus', $barcode)
                ->with('token') // Подтягиваем информацию о токене
                ->get();

            if ($orders->isNotEmpty()) {
                // Подготавливаем данные для отображения
                $orderInfo = [
                    'barcode' => $barcode,
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'article' => $order->article,
                            'token_name' => $order->token->name ?? 'Неизвестный токен',
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                            'supply_id' => $order->supply_id,
                        ];
                    }),
                ];
            } else {
                return redirect()
                    ->route('orders.search')
                    ->withErrors(['Товар не найден в заказах']);
            }
        }

        return view('orders.scan', compact('orderInfo'));
    }
}
