<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('FINANCE_CURRENCY', 'RM'),

    /*
    |--------------------------------------------------------------------------
    | Sales Tax
    |--------------------------------------------------------------------------
    |
    | Default tax rate (percent) for new sales invoices, e.g. 8 for SST.
    |
    */

    'default_tax_rate' => (float) env('FINANCE_DEFAULT_TAX_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Payment Terms
    |--------------------------------------------------------------------------
    |
    | Days until a new invoice or bill is due.
    |
    */

    'payment_terms_days' => (int) env('FINANCE_PAYMENT_TERMS_DAYS', 30),

];
