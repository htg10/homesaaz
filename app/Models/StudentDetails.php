<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDetails extends Model
{
    protected $table = 'StudentDetails';
    protected $primaryKey = 'StudentdetID';
    public $timestamps = false;
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [
        'StudentName',
        'address',
        'FatherName',
        'RoomNo',
        'BedNo',
        'RentAmt',
        'RentDate',
        'Location',
        'StudentImage',
        'MobileNo',
        'StudentDeskTopID',
        'StartingDate',
        'EndingDate',
        'RentPayDay',
    ];
}