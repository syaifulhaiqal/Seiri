<?php

namespace App\Models;

use App\Models\Concerns\IsLineItem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'description', 'quantity', 'unit_price', 'amount', 'sort'])]
class QuotationLine extends Model
{
    use IsLineItem;
}
