<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRent extends Model
{
    protected $table = 'PendingRent';
    protected $primaryKey = 'RentPendingID';
    public $timestamps = false;
    protected $fillable = [
        'RentAmount',
        'RecAmount',
        'BalAmount',
        'Name',
        'RoomNo',
        'LocationName',
        'RentDate',
        'Locationcode',
        'StudentImage',
    ];
}