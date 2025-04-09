<?php

namespace App\Console\Commands;

use App\Models\Interval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IntervalsList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intervals:list {--left=* : Left bound of the interval} {--right=* : Right bound of the interval}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List intervals intersecting with [left, right]';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $left = $this->option('left');
        $right = $this->option('right');

        // Проверяем корректность аргументов
        if (!isset($left[0]) || !isset($right[0])) {
            $this->error('Both --left and --right parameters are required');
            return 1;
        }

        $left = (int) $left[0];
        $right = (int) $right[0];

        if ($left > $right) {
            $this->error('Left bound should be less than or equal to right bound');
            return 1;
        }

        // Получаем пересекающиеся интервалы используя Query Builder
        // Условия пересечения:
        // 1. Для отрезков: start <= right AND end >= left
        // 2. Для лучей (end IS NULL): start <= right

        $intervals = DB::table('intervals')
            ->where(function ($query) use ($left, $right) {
                $query->where(function ($q) use ($left, $right) {
                    $q->where('start', '<=', $right)
                        ->whereNotNull('end')
                        ->where('end', '>=', $left);
                })
                    ->orWhere(function ($q) use ($right) {
                        $q->where('start', '<=', $right)
                            ->whereNull('end');
                    });
            })
            ->get();

        if ($intervals->isEmpty()) {
            $this->info("No intervals found intersecting with [{$left}, {$right}]");
            return 0;
        }

        // Формируем данные для вывода в виде таблицы
        $headers = ['ID', 'Start', 'End', 'Type'];
        $rows = [];

        foreach ($intervals as $interval) {
            $rows[] = [
                'id' => $interval->id,
                'start' => $interval->start,
                'end' => $interval->end ?? 'Infinity',
                'type' => $interval->end === null ? 'Ray' : 'Segment'
            ];
        }

        // Выводим таблицу
        $this->table($headers, $rows);
        $this->info(count($rows) . " intervals found intersecting with [{$left}, {$right}]");

        return 0;
    }
}
