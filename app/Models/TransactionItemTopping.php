<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TransactionItemTopping extends Model
{
    use HasUuids;

    protected $fillable = ['transaction_item_id', 'topping_id', 'topping_name', 'price_each'];
}
