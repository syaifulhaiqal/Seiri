<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Working Hours
    |--------------------------------------------------------------------------
    |
    | Clocking in after `work_start` marks the day as late. Times are in the
    | app timezone, 24-hour "HH:MM".
    |
    */

    'work_start' => env('HR_WORK_START', '09:00'),

    /*
    |--------------------------------------------------------------------------
    | Employee Numbers
    |--------------------------------------------------------------------------
    |
    | New employees get the next number with this prefix, e.g. EMP-0001.
    |
    */

    'employee_no_prefix' => env('HR_EMPLOYEE_NO_PREFIX', 'EMP-'),

];
