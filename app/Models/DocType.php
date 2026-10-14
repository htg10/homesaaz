<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocType extends Model
{
    protected $table = 'DocType';
    protected $primaryKey = 'DocTypeID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'DocTypeName',
    ];

    /**
     * Reverse relationship with DocumentPrint
     */
    public function documents()
    {
        return $this->hasMany(DocumentDownload::class, 'DocType', 'DocTypeID');
    }
}