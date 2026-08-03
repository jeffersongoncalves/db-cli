<?php

namespace App\Commands;

use App\Concerns\FormatsOutput;
use App\Exceptions\UnsafeQueryException;
use App\Services\ConnectionService;
use App\Services\DatabaseService;
use LaravelZero\Framework\Commands\Command;

class BenchmarkCommand extends Command
{
    use FormatsOutput;

    protected $signature = 'benchmark {connection : Saved connection profile name} {sql? : Read-only SQL statement (omit when using --file)}
        {--file= : Path to a .sql file with one or more ;-terminated statements}
        {--runs=10 : Executions measured per query}
        {--warmup=1 : Extra executions before measuring, discarded}
        {--database= : Override the profile\'s database (same server, different database)}
        {--format=table : table|json|csv}';

    protected $description = 'Time read-only SQL statements over N runs (min/avg/median/max)';

    public function handle(ConnectionService $connections, DatabaseService $database): int
    {
        $sqlArg = $this->argument('sql');
        $file = $this->option('file');

        if ($sqlArg === null && $file === null) {
            $this->components->error('Provide a SQL statement or --file=path.sql.');

            return self::FAILURE;
        }

        if ($file !== null && ! is_file((string) $file)) {
            $this->components->error("File not found: {$file}");

            return self::FAILURE;
        }

        $queries = $file !== null ? $this->parseFile((string) $file) : ['q1' => (string) $sqlArg];

        $connection = $connections->getOrFail((string) $this->argument('connection'));

        if ($override = $this->option('database')) {
            $connection = $connection->withDatabase((string) $override);
        }

        $pdo = $database->connect($connection);
        $runs = max(1, (int) $this->option('runs'));
        $warmup = max(0, (int) $this->option('warmup'));

        $results = [];

        foreach ($queries as $label => $sql) {
            try {
                $sample = $database->benchmark($pdo, $sql, $runs, $warmup);
            } catch (UnsafeQueryException $exception) {
                $this->components->error("[{$label}] {$exception->getMessage()}");

                return self::FAILURE;
            }

            $times = $sample['times'];
            sort($times);
            $count = count($times);

            $results[] = [
                'query' => $label,
                'runs' => $count,
                'min_ms' => round($times[0], 2),
                'avg_ms' => round(array_sum($times) / $count, 2),
                'median_ms' => round($times[intdiv($count, 2)], 2),
                'max_ms' => round($times[$count - 1], 2),
                'rows' => $sample['rows'],
            ];
        }

        $this->renderRows($results, (string) $this->option('format'));

        return self::SUCCESS;
    }

    /**
     * Splits a .sql file into labeled statements. A `-- label` comment line
     * inside a statement becomes its label; otherwise it's q1, q2, ...
     *
     * @return array<string, string>
     */
    private function parseFile(string $path): array
    {
        $chunks = array_values(array_filter(array_map('trim', explode(';', (string) file_get_contents($path)))));

        $queries = [];

        foreach ($chunks as $i => $chunk) {
            $label = 'q'.($i + 1);

            if (preg_match('/^--\s*(.+)$/m', $chunk, $matches) === 1) {
                $label = trim($matches[1]);
                $chunk = trim((string) preg_replace('/^--.*$/m', '', $chunk));
            }

            $queries[$label] = $chunk;
        }

        return $queries;
    }
}
