<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewStudent extends Model
{
    protected $table = 'StudentNewAdmission';
    protected $primaryKey = 'NewAdmissionID';
    public $timestamps = false;
    protected $fillable = [
        'FirstName',
        'LastName',
        'MobileNo',
        'Addarcard',
        'Vaccination',
        'AddharCardAtt',
        'Icard',
        'Location',
        'FatherName',
        'MotherName',
        'FatherMobileNo',
        'MotherMobileNo',
        'Remarks',
        'TrfToMain',
    ];
}