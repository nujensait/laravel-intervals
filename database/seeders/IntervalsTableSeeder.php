<?php

namespace Database\Seeders;

use App\Models\Interval;
use Illuminate\Database\Seeder;

class IntervalsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Очистка таблицы перед заполнением
        Interval::truncate();

        // Создаем 10,000 случайных записей
        $intervals = [];

        for ($i = 0; $i < 10000; $i++) {
            $start = rand(1, 1000);

            // Будем делать 20% записей лучами (с null в end)
            $isRay = rand(1, 100) <= 20;
            $end = $isRay ? null : $start + rand(1, 500);

            $intervals[] = [
                'start' => $start,
                'end' => $end,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Вставляем данные пакетами для оптимизации
        $chunks = array_chunk($intervals, 1000);
        foreach ($chunks as $chunk) {
            Interval::insert($chunk);
        }
    }
}
