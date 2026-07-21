<?php

namespace App\Concerns;

use JeffersonGoncalves\LaravelZero\Console\FormatsOutput as BaseFormatsOutput;

trait FormatsOutput
{
    use BaseFormatsOutput;

    /**
     * Renders rows as table (human), json, or csv (for LLM/pipe consumption).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function renderRows(array $rows, string $format): void
    {
        match ($format) {
            'json' => $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]'),
            'csv' => $this->renderCsv($rows),
            default => $this->renderTable(array_keys($rows[0] ?? []), $this->stringifyRows($rows)),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function renderCsv(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $stream = fopen('php://output', 'w');

        fputcsv($stream, array_keys($rows[0]));

        foreach ($this->stringifyRows($rows) as $row) {
            fputcsv($stream, $row);
        }

        fclose($stream);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<int|string, string>>
     */
    private function stringifyRows(array $rows): array
    {
        return array_map(
            static fn (array $row) => array_map(
                static fn (mixed $value) => match (true) {
                    $value === null => 'NULL',
                    is_bool($value) => $value ? '1' : '0',
                    is_scalar($value) => (string) $value,
                    default => json_encode($value) ?: '',
                },
                $row
            ),
            $rows
        );
    }
}
