<?php
require_once __DIR__ . '/Result.php';

/**
 * COMPUTING (general part): base class with reusable statistics helpers.
 * It is abstract because it doesn't know which questions to ask;
 * a subclass for a specific dataset implements analyze().
 */
abstract class Analyzer
{
    protected array $records;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

    /** @return Result[] one Result per question */
    abstract public function analyze(): array;

    public function getRecordCount(): int
    {
        return count($this->records);
    }

    /** Frequency table: value => how many records have that value in $field */
    protected function countBy(string $field, ?array $records = null): array
    {
        $records = $records ?? $this->records;   // default to all records
        $counts = [];
        foreach ($records as $record) {
            $key = $record[$field] ?? 'Unknown';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        return $counts;
    }

    /** Highest $n entries of a frequency table, as [value, count] pairs */
    protected function topN(array $counts, int $n = 1): array
    {
        arsort($counts);   // sort by count, highest first, keeping the keys
        $top = [];
        foreach ($counts as $value => $count) {
            if (count($top) >= $n) {
                break;
            }
            array_push($top, [$value, $count]);
        }
        return $top;
    }

    /** Mean of a list of numbers, rounded to $decimals places */
    protected function average(array $values, int $decimals = 2): float
    {
        if (count($values) === 0) {
            return 0;
        }
        $sum = 0;
        for ($i = 0; $i < count($values); $i++) {
            $sum += $values[$i];
        }
        return round($sum / count($values), $decimals);
    }

    protected function median(array $values): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0;
        }
        sort($values);   // PHP passes arrays by value, so the caller's array isn't changed
        $mid = intdiv($n, 2);
        return $n % 2 === 0 ? ($values[$mid - 1] + $values[$mid]) / 2 : $values[$mid];
    }

    /** "part out of whole" as a percentage string, e.g. "48.2%" */
    protected function percent(int $part, int $whole, int $decimals = 1): string
    {
        if ($whole === 0) {
            return '0%';
        }
        return number_format($part / $whole * 100, $decimals) . '%';
    }
}
