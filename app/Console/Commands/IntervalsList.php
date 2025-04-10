<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IntervalsList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intervals:list {--left=* : Left bound of the interval} {--right=* : Right bound of the interval}  {--json : Output results as JSON}';

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
        // Изменяем проверку аргументов
        $left = $this->option('left');
        $right = $this->option('right');

        // Проверяем, были ли переданы аргументы (строгое сравнение с null)
        if ($left === null || $right === null) {
            $this->error('Both --left and --right parameters are required');
            return 1;
        }

        // Проверяем, что значения не пустые
        if ($left === '' || $right === '') {
            $this->error('Both --left and --right parameters must have values');
            return 1;
        }

        $left = (int) $left;
        $right = (int) $right;

        if ($left > $right) {
            $this->error('Left bound should be less than or equal to right bound');
            return 1;
        }

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
            ->orderBy('id')
            ->get();

        if ($this->option('json')) {
            $result = $intervals->map(function ($interval) {
                return [
                    'id' => $interval->id,
                    'start' => $interval->start,
                    'end' => $interval->end,
                    'type' => $interval->end === null ? 'Ray' : 'Segment'
                ];
            });

            $this->line($result->toJson());
            return 0;
        }

        // Изменяем вывод при отсутствии интервалов
        if ($intervals->isEmpty()) {
            $this->line("No intervals found intersecting with [{$left}, {$right}]"); // Используем line вместо info
            return 0;
        }

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

        $this->table($headers, $rows);
        $this->info(count($rows) . " intervals found intersecting with [{$left}, {$right}]");

        return 0;
    }
}
