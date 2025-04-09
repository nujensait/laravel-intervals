<?php

/**
 * Test cmd: /app/Console/Commands/IntervalsList.php
 * Usage:
 * php artisan test --filter=IntervalsCommandTest
 */

namespace Tests\Feature;

use App\Models\Interval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntervalsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Настройка тестовых данных
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовые интервалы
        $intervals = [
            // Отрезки
            ['start' => 10, 'end' => 20],   // пересекается с [15, 30]
            ['start' => 15, 'end' => 25],   // пересекается с [15, 30]
            ['start' => 25, 'end' => 35],   // пересекается с [15, 30]
            ['start' => 5, 'end' => 15],    // пересекается с [15, 30]
            ['start' => 30, 'end' => 40],   // пересекается с [15, 30]
            ['start' => 0, 'end' => 10],    // не пересекается с [15, 30]
            ['start' => 40, 'end' => 50],   // не пересекается с [15, 30]

            // Лучи
            ['start' => 10, 'end' => null], // пересекается с [15, 30]
            ['start' => 20, 'end' => null], // пересекается с [15, 30]
            ['start' => 40, 'end' => null]  // не пересекается с [15, 30]
        ];

        foreach ($intervals as $interval) {
            Interval::create($interval);
        }
    }

    /**
     * Тест корректной работы команды intervals:list
     *
     * @return void
     */
    public function testIntervalsListCommand()
    {
        // Выполняем команду с аргументами
        $this->artisan('intervals:list', [
            '--left' => 15,
            '--right' => 30,
        ])
            ->expectsTable(
                ['ID', 'Start', 'End', 'Type'],
                [
                    [1, 10, 20, 'Segment'],
                    [2, 15, 25, 'Segment'],
                    [3, 25, 35, 'Segment'],
                    [4, 5, 15, 'Segment'],
                    [5, 30, 40, 'Segment'],
                    [8, 10, 'Infinity', 'Ray'],
                    [9, 20, 'Infinity', 'Ray'],
                ]
            )
            ->assertExitCode(0);
    }

    /**
     * Тест отсутствия пересечений
     *
     * @return void
     */
    public function testIntervalsListWithNoIntersections()
    {
        // Проверяем случай, когда нет пересечений
        $this->artisan('intervals:list', [
            '--left' => 51,
            '--right' => 60,
        ])
            ->expectsOutput('No intervals found intersecting with [51, 60]')
            ->assertExitCode(0);
    }

    /**
     * Тест валидации некорректных аргументов
     *
     * @return void
     */
    public function testIntervalsListWithInvalidArguments()
    {
        // Проверяем случай, когда left > right
        $this->artisan('intervals:list', [
            '--left' => 30,
            '--right' => 15,
        ])
            ->expectsOutput('Left bound should be less than or equal to right bound')
            ->assertExitCode(1);

        // Проверяем случай, когда отсутствуют аргументы
        $this->artisan('intervals:list')
            ->expectsOutput('Both --left and --right parameters are required')
            ->assertExitCode(1);
    }

    /**
     * Тест граничных значений интервалов
     *
     * @return void
     */
    public function testIntervalsBoundaryConditions()
    {
        // Создаем тестовые интервалы для граничных случаев
        Interval::create(['start' => 50, 'end' => 50]); // Точка (интервал нулевой длины)

        // Тестируем точное совпадение
        $this->artisan('intervals:list', [
            '--left' => 50,
            '--right' => 50,
        ])
            ->assertExitCode(0)
            ->expectsTable(
                ['ID', 'Start', 'End', 'Type'],
                [
                    [11, 50, 50, 'Segment'],
                    [8, 10, 'Infinity', 'Ray'],
                    [9, 20, 'Infinity', 'Ray'],
                ]
            );
    }

    /**
     * Тест больших чисел и отрицательных значений
     *
     * @return void
     */
    public function testIntervalsWithLargeAndNegativeNumbers()
    {
        // Создаем интервалы с большими и отрицательными числами
        Interval::create(['start' => -100, 'end' => -50]);
        Interval::create(['start' => -30, 'end' => -10]);
        Interval::create(['start' => -20, 'end' => 5]);
        Interval::create(['start' => -200, 'end' => null]); // Луч

        // Тест с отрицательными числами
        $this->artisan('intervals:list', [
            '--left' => -40,
            '--right' => -20,
        ])
            ->assertExitCode(0)
            ->expectsTable(
                ['ID', 'Start', 'End', 'Type'],
                [
                    [12, -100, -50, 'Segment'],
                    [13, -30, -10, 'Segment'],
                    [14, -20, 5, 'Segment'],
                    [15, -200, 'Infinity', 'Ray'],
                ]
            );

        // Тест с большими числами
        Interval::create(['start' => 10000, 'end' => 20000]);

        $this->artisan('intervals:list', [
            '--left' => 15000,
            '--right' => 25000,
        ])
            ->assertExitCode(0)
            ->expectsTable(
                ['ID', 'Start', 'End', 'Type'],
                [
                    [16, 10000, 20000, 'Segment'],
                    [8, 10, 'Infinity', 'Ray'],
                    [9, 20, 'Infinity', 'Ray'],
                    [15, -200, 'Infinity', 'Ray'],
                ]
            );
    }
}
