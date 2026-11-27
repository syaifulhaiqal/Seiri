<?php

/*
|--------------------------------------------------------------------------
| Statutory Contributions (Malaysia)
|--------------------------------------------------------------------------
|
| Simplified percentage rates. The official KWSP, PERKESO and LHDN schedules
| use wage bands, so check the figures against them before paying real
| salaries, and update these rates when the law changes.
|
*/

return [

    'epf' => [
        'employee_rate' => 11.0,
        // Employer pays the higher rate on wages up to the threshold.
        'employer_rate' => 13.0,
        'employer_rate_above_threshold' => 12.0,
        'threshold' => 5000,
        // KWSP rounds contributions up to the next ringgit.
        'round_up_to_ringgit' => true,
    ],

    'socso' => [
        'employee_rate' => 0.5,
        'employer_rate' => 1.75,
        // Wages above this are not counted.
        'wage_ceiling' => 6000,
    ],

    'eis' => [
        'employee_rate' => 0.2,
        'employer_rate' => 0.2,
        'wage_ceiling' => 6000,
    ],

];
