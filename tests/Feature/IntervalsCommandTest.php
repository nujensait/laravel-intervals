<?php

namespace Tests\Feature;

use App\Models\Interval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntervalsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $intervals = [
            ['start' => 10, 'end' => 20],
            ['start' => 15, 'end' => 25],
            ['start' => 25, 'end' => 35],
            ['start' => 5, 'end' => 15],
            ['start' => 30, 'end' => 40],
            ['start' => 0, 'end' => 10],
            ['start' => 40, 'end' => 50],
            ['start' => 10, 'end' => null],
            ['start' => 20, 'end' => null],
            ['start' => 40, 'end' => null]
        ];

        foreach ($intervals as $interval) {
            Interval::create($interval);
        }
    }

    public function testIntervalsListCommand()
    {
        $this->artisan('intervals:list', [
            '--left' => 15,
            '--right' => 30,
        ])->assertExitCode(0);
    }

    public function testIntervalsListWithNoIntersections()
    {
        // Запускаем команду и сохраняем вывод
        $output = $this->artisan('intervals:list', [
            '--left' => 51,
            '--right' => 60,
        ]);

        // Проверяем ожидания
        //$output->expectsOutput("No intervals found intersecting with [51, 60]");
        $output->assertExitCode(0);
    }

    public function testIntervalsListWithInvalidArguments()
    {
        // Тест с left > right
        $output = $this->artisan('intervals:list', [
            '--left' => 30,
            '--right' => 15,
        ]);

        //$output->expectsOutput('Left bound should be less than or equal to right bound');
        $output->assertExitCode(1);
    }

    public function testIntervalsBoundaryConditions()
    {
        Interval::create(['start' => 50, 'end' => 50]);

        $this->artisan('intervals:list', [
            '--left' => 50,
            '--right' => 50,
        ])->assertExitCode(0);
    }

    public function testIntervalsWithLargeAndNegativeNumbers()
    {
        Interval::create(['start' => -100, 'end' => -50]);
        Interval::create(['start' => -30, 'end' => -10]);
        Interval::create(['start' => -20, 'end' => 5]);
        Interval::create(['start' => -200, 'end' => null]);

        $this->artisan('intervals:list', [
            '--left' => -40,
            '--right' => -20,
        ])->assertExitCode(0);

        Interval::create(['start' => 10000, 'end' => 20000]);

        $this->artisan('intervals:list', [
            '--left' => 15000,
            '--right' => 25000,
        ])->assertExitCode(0);
    }

    // Дополнительный тест для JSON вывода
    public function testJsonOutput()
    {
        $this->artisan('intervals:list', [
            '--left' => 15,
            '--right' => 30,
            '--json' => true,
        ])->assertExitCode(0);
    }
}
