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

    /*
    |--------------------------------------------------------------------------
    | Office QR Clock-in
    |--------------------------------------------------------------------------
    |
    | The QR code on the office screen changes every `qr_refresh_seconds`, and a
    | scanned code works for `qr_valid_minutes` (time to sign in on the phone).
    | A photo of the code sent home stops working once it expires.
    |
    | `office_ips`: optional comma-separated public IP addresses of the office
    | network. When set, QR clock-ins from any other network are refused.
    |
    */

    'qr_refresh_seconds' => (int) env('HR_QR_REFRESH_SECONDS', 30),

    'qr_valid_minutes' => (int) env('HR_QR_VALID_MINUTES', 5),

    'office_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('HR_OFFICE_IPS', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Overtime
    |--------------------------------------------------------------------------
    |
    | Hourly rate = monthly basic salary / `days_per_month` / `hours_per_day`
    | (Employment Act 1955). Overtime is paid in the next payroll, is not
    | subject to EPF, and counts for SOCSO and EIS.
    |
    */

    'overtime' => [
        'days_per_month' => (int) env('HR_OVERTIME_DAYS_PER_MONTH', 26),
        'hours_per_day' => (int) env('HR_OVERTIME_HOURS_PER_DAY', 8),
        'rates' => [
            'normal' => 1.5,
            'rest_day' => 2.0,
            'public_holiday' => 3.0,
        ],
    ],

];
