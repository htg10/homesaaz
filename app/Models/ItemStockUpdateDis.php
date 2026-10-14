<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemStockUpdateDis extends Model
{
    protected $table = 'ItemStockUpdateDis';

    public $timestamps = false;

    protected $fillable = [
        'ItemCode',
        'MRP',
        'Discount',
        'Image'
    ];
}