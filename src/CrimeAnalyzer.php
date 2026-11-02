<?php
require_once __DIR__ . '/Analyzer.php';
require_once __DIR__ . '/InvalidDatasetException.php';

/**
 * COMPUTING (dataset-specific part): the 10 questions about
 * Open Data DC's "Crime Incidents in 2025". Inherits the helpers from Analyzer.
 */
class CrimeAnalyzer extends Analyzer
{
    private const SECONDS_PER_DAY = 60 * 60 * 24;

    // Fields the 10 questions depend on (BID and START_DATE are allowed to be empty)
    private const REQUIRED_FIELDS = ['OFFENSE', 'WARD', 'SHIFT', 'METHOD', 'REPORT_DAT', 'BLOCK'];

    public function __construct(array $records)
    {
        parent::__construct($records);
        $this->validate();
    }

    /**
     * Checks every record before any statistics are computed, so valid JSON
     * from a different dataset gets a clear error instead of meaningless answers.
     * @throws InvalidDatasetException
     */
    private function validate(): void
    {
        foreach ($this->records as $i => $record) {
            $number = $i + 1;   // human-friendly record number for the message
            if (!is_array($record)) {
                throw new InvalidDatasetException("record #$number is not an object");
            }
            foreach (self::REQUIRED_FIELDS as $field) {
                if (!isset($record[$field]) || $record[$field] === '') {
                    throw new InvalidDatasetException("record #$number is missing the \"$field\" field");
                }
            }
            if (strtotime($record['REPORT_DAT']) === false) {
                throw new InvalidDatasetException("record #$number has an invalid REPORT_DAT date");
            }
        }
    }

    public function analyze(): array
    {
        $total = $this->getRecordCount();
        $results = [];

        // Q1: frequency table of offenses, then the top entry
        [$offense, $count] = $this->topN($this->countBy('OFFENSE'))[0];
        array_push($results, new Result(
            'What is the most common offense type, and how many times did it occur?',
            "$offense: $count incidents ({$this->percent($count, $total)} of all incidents)"
        ));

        // Q2: the frequency table has one key per distinct offense
        $offenseTypes = count($this->countBy('OFFENSE'));
        array_push($results, new Result(
            'How many different offense types appear in the data?',
            "$offenseTypes offense types"
        ));

        // Q3: kept in $topWard because Q10 reuses it
        [$topWard, $wardCount] = $this->topN($this->countBy('WARD'))[0];
        array_push($results, new Result(
            'Which ward had the most incidents?',
            "Ward $topWard, with $wardCount incidents"
        ));

        // Q4: percentage for every shift
        $shiftParts = [];
        foreach ($this->countBy('SHIFT') as $shift => $shiftCount) {
            array_push($shiftParts, "$shift: {$this->percent($shiftCount, $total)}");
        }
        array_push($results, new Result(
            'What percentage of incidents happened in each shift?',
            implode(', ', $shiftParts)
        ));

        // Q5: array_filter keeps only gun incidents
        $gunCount = count(array_filter($this->records, fn($r) => ($r['METHOD'] ?? '') === 'GUN'));
        array_push($results, new Result(
            'What percentage of incidents involved a gun?',
            "{$this->percent($gunCount, $total)} ($gunCount of $total)"
        ));

        // Q6: turn each report date into a month name ("July"), then count by month
        $monthCounts = [];
        foreach ($this->records as $r) {
            $month = date('F', strtotime($r['REPORT_DAT']));
            $monthCounts[$month] = ($monthCounts[$month] ?? 0) + 1;
        }
        [$topMonth, $monthCount] = $this->topN($monthCounts)[0];
        array_push($results, new Result(
            'Which month of 2025 had the most reported incidents?',
            "$topMonth, with $monthCount incidents"
        ));

        // Q7: filter out missing dates -> map to days between start and report -> drop negatives
        $withDates = array_filter($this->records, fn($r) => !empty($r['START_DATE']) && !empty($r['REPORT_DAT']));
        $delays = array_map(
            fn($r) => (strtotime($r['REPORT_DAT']) - strtotime($r['START_DATE'])) / self::SECONDS_PER_DAY,
            $withDates
        );
        // array_filter keeps the original keys, so array_values re-numbers them 0,1,2... for the for loop in average()
        $delays = array_values(array_filter($delays, fn($d) => $d >= 0));
        $avgDelay = $this->average($delays);
        $medianDelay = number_format($this->median($delays), 2);
        array_push($results, new Result(
            'On average, how many days passed between when an incident started and when it was reported?',
            "$avgDelay days on average (median $medianDelay days)"
        ));

        // Q8
        [$block, $blockCount] = $this->topN($this->countBy('BLOCK'))[0];
        array_push($results, new Result(
            'Which block had the most incidents?',
            "$block, with $blockCount incidents"
        ));

        // Q9: BID is null when the incident is outside a Business Improvement District
        $bidCount = count(array_filter($this->records, fn($r) => !empty($r['BID'])));
        array_push($results, new Result(
            'What percentage of incidents occurred inside a Business Improvement District (BID)?',
            "{$this->percent($bidCount, $total)} ($bidCount incidents)"
        ));

        // Q10: only the top ward's records, then the most common offense among them
        $wardRecords = array_filter($this->records, fn($r) => ($r['WARD'] ?? '') == $topWard);
        [$wardOffense, $wardOffenseCount] = $this->topN($this->countBy('OFFENSE', $wardRecords))[0];
        array_push($results, new Result(
            'In the ward with the most incidents, what is the most common offense?',
            "In Ward $topWard: $wardOffense, with $wardOffenseCount incidents"
        ));

        return $results;
    }
}
