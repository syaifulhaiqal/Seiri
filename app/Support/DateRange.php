<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Short date ranges: "Fri, 6 Mar 2026", "6–10 Mar 2026" or "28 Feb – 3 Mar 2026".
 */
class DateRange
{
    public static function format(CarbonInterface $start, CarbonInterface $end): string
    {
        return match (true) {
            $start->isSameDay($end) => $start->translatedFormat('D, j M Y'),
            $start->isSameMonth($end) => $start->format('j') . '–' . $end->translatedFormat('j M Y'),
            default => $start->translatedFormat('j M') . ' – ' . $end->translatedFormat('j M Y'),
        };
    }
}
