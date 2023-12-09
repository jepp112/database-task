<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;

class Bench extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bench';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->clean(); //remove indexes and columns form previous test

        $initial = DB::table('deals')
            ->selectRaw('
            DATE_FORMAT(created_at, "%Y-%m-%d") `created_date`,
            status_id,
            COUNT(id) `count`
            ')
            ->whereBetween('created_at', [
                '2023-01-01 00:00:00',
                '2023-01-31 23:59:59',
            ])
            ->groupByRaw('1, 2');

        $initialResults = Benchmark::measure(fn () => $initial->get(), 3);

        //STEP 1 - improve count

        $step1 = DB::table('deals')
            ->selectRaw('
            DATE_FORMAT(created_at, "%Y-%m-%d") `created_date`,
            status_id,
            COUNT(*) `count`
            ')
            ->whereBetween('created_at', [
                '2023-01-01 00:00:00',
                '2023-01-31 23:59:59',
            ])
            ->groupByRaw('1, 2');

        $step1Results = Benchmark::measure(fn () => $step1->get(), 3);

        //STEP-2 use DATE instead DATE_FORMAT
        $step2 = DB::table('deals')
            ->selectRaw('
            DATE(created_at) `created_date`,
            status_id,
            COUNT(*) `count`
            ')
            ->whereBetween('created_at', [
                '2023-01-01 00:00:00',
                '2023-01-31 23:59:59',
            ])
            ->groupByRaw('1, 2');

        $step2Results = Benchmark::measure(fn () => $step2->get(), 3);

        //STEP 3 - add created_date generated column
        DB::statement('ALTER TABLE deals ADD COLUMN created_date DATE GENERATED ALWAYS AS (DATE(created_at)) STORED;');

        $step3 = DB::table('deals')
            ->selectRaw('
            created_date,
            status_id,
            COUNT(*) `count`
            ')
            ->whereBetween('created_date', [
                '2023-01-01',
                '2023-01-31',
            ])
            ->groupByRaw('created_date, status_id');

        $step3Results = Benchmark::measure(fn () => $step3->get(), 3);

        //STEP 4 - add composite index on created_date and status_id

        DB::statement('ALTER TABLE deals ADD INDEX created_date_status_id_index (created_date, status_id)');

        $step4Results = Benchmark::measure(fn () => $step3->get(), 3);

        dd([
            'initial' => $initialResults,
            'step-1' => $step1Results,
            'step-2' => $step2Results,
            'step-3' => $step3Results,
            'step-4' => $step4Results,
        ]);
    }

    public function clean(): void
    {
        try {
            $indexes = DB::select('SHOW INDEX FROM deals');

            foreach ($indexes as $index) {
                $indexName = $index->Key_name;

                if ($indexName === 'PRIMARY') {
                    continue;
                }

                DB::statement("ALTER TABLE deals DROP INDEX $indexName");
            }
        } catch (\Throwable) {

        }

        try {
            DB::statement('ALTER TABLE deals DROP COLUMN created_date');
        } catch (\Throwable $throwable) {

        }
    }
}
