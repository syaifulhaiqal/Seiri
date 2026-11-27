<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Malaysia's fixed-date national holidays for this year and next. Moving holidays (Hari Raya, Chinese New Year,
 * Deepavali, Wesak, ...) change every year, so HR adds them from the gazette under Public holidays.
 */
class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([now()->year, now()->year + 1] as $year) {
            $holidays = [
                "{$year}-01-01" => 'New Year\'s Day',
                "{$year}-05-01" => 'Labour Day',
                // The Agong's official birthday is the first Monday of June.
                Carbon::parse("first monday of june {$year}")->format('Y-m-d') => 'Birthday of the Yang di-Pertuan Agong',
                "{$year}-08-31" => 'National Day (Hari Merdeka)',
                "{$year}-09-16" => 'Malaysia Day',
                "{$year}-12-25" => 'Christmas Day',
            ];

            foreach ($holidays as $date => $name) {
                Holiday::query()->firstOrCreate(['date' => $date], ['name' => $name]);
            }
        }
    }
}
