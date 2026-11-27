<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceType;

/**
 * Sales invoices to customers.
 */
class InvoiceController extends DocumentController
{
    protected function type(): InvoiceType
    {
        return InvoiceType::Sales;
    }
}
