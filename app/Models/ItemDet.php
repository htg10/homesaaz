<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDet extends Model
{
    protected $table = 'ItemDet';
    public $timestamps = false;

    protected $fillable = [
        'SupplierName',
    ];
}