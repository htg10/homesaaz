<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BedOccupancy extends Model
{
    protected $table = 'BedOccupancy';
    protected $primaryKey = 'BedOccID';
    public $timestamps = false;
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [
        'LocationName',
        'Letout',
        'Vacant',
        'TOTALBED',
    ];
}