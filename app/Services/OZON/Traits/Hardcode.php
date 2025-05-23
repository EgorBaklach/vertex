<?php namespace App\Services\OZON\Traits;

trait Hardcode
{
    // HARDCODE!!!
    private const dependencies = [
        'categories' => [
            41777453 => 200000933, // Одежда - Свитшоты Ex. # 16376283
        ],
        'types' => [
            257364480 => 91971, // Наклейка интерьерная - "Набор виниловых стикеров (наклеек) Stickr 16 шт.",
            99002 => 94038, // Сумка на плечо - "Сумка - шоппер / 31х42 см / Птицы / Совы Сова в деревьях," # 212170055
        ],
        'properties' => [
            4621 => 4596,   // Тип рукава
            10237 => 6643,  // Материал Наполнителя,
            7752 => 5309,   // Материал - Шапочки для плавания,
            57 => 10096,    // Цвет товара - Рашгард мужской,
            5300 => 22321,  // Высота ручек, см - "Сумка-шоппер / 31х42 см / Птицы / Совы Сова в деревьях" # 212170055
            5304 => 22323,  // Длина плечевого ремня, см - "Сумка-шоппер / 31х42 см / Птицы / Совы Сова в деревьях" # 212170055
            9957 => 4497,   // Вес с упаковкой - "Леггинсы женские Burnettie / Тайтсы спортивные / Одежда для активного отдыха и фитнеса" # 180753483
            5319 => 22328,  // Колво внутренних отделений - "Шоппер (сумка) 33х40 см, Gorolla" # 58099657

            5322 => null,   // ХЗ что это со значением "0" - Шоппер (сумка) 33х40 см, Gorolla
            6602 => null,   // ХЗ что это со значением "1" - Набор виниловых стикеров (наклеек) Stickr 16 шт. "Аестхетиcлифе_стиcкерс_часть_1"
            23075 => null,  // ХЗ что это со значением "Сетка" - Футболка женская 3D # 1136099885
        ],
    ];
}
