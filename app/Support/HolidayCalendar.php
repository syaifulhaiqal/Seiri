<?php

namespace App\Support;

use App\Models\Holiday;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

/**
 * Working days: Monday to Friday, except public holidays. One instance per request (see AppServiceProvider),
 * so each year's holidays are read once.
 */
class HolidayCalendar
{
    /**
     * @var array<int, array<string, string>> year => [Y-m-d => name]
     */
    protected array $years = [];

    public function isHoliday(CarbonInterface $day): bool
    {
        return $this->nameOf($day) !== null;
    }

    public function nameOf(CarbonInterface $day): null|string
    {
        return $this->year($day->year)[$day->format('Y-m-d')] ?? null;
    }

    public function isWorkingDay(CarbonInterface $day): bool
    {
        return ! $day->isWeekend() && ! $this->isHoliday($day);
    }

    /**
     * Working days between two dates, both included.
     */
    public function workingDays(CarbonInterface $start, CarbonInterface $end): int
    {
        if ($end->lt($start)) {
            return 0;
        }

        return collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
            ->filter(fn (CarbonInterface $day) => $this->isWorkingDay($day))
            ->count();
    }

    public function flush(): void
    {
        $this->years = [];
    }

    /**
     * @return array<string, string>
     */
    protected function year(int $year): array
    {
        return $this->years[$year] ??= Holiday::query()
            ->whereYear('date', $year)
            ->get(['date', 'name'])
            ->mapWithKeys(fn (Holiday $holiday) => [$holiday->date->format('Y-m-d') => $holiday->name])
            ->all();
    }
}
