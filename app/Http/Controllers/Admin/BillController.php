<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceType;

/**
 * Bills from suppliers.
 */
class BillController extends DocumentController
{
    protected function type(): InvoiceType
    {
        return InvoiceType::Purchase;
    }
}
