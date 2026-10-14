<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Location extends Model
{
    protected $table = 'Location';

    protected $primaryKey = 'LocationID';

    public $timestamps = false;

    protected $fillable = [
        'Locationcode',
        'LocationName',
        'LocationAddress',
        'Color',
        'GST',
        'QRCODE',
    ];

    // GST is a GSTIN string, e.g. 06AAFCV0519D1ZQ.
    // Do NOT cast it to decimal.
    protected $casts = [
        'LocationID' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute()
    {
        return trim($this->LocationName . ' (' . $this->Locationcode . ')');
    }

    public function getHasGstAttribute()
    {
        return !is_null($this->GST) && trim((string) $this->GST) !== '';
    }

    public function getHasColorAttribute()
    {
        return !empty($this->Color);
    }

    public function getGstPercentageAttribute()
    {
        // GST now stores GSTIN, not a numeric percentage.
        return $this->has_gst
            ? trim((string) $this->GST)
            : '-';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query
            ->whereNotNull('LocationName')
            ->where('LocationName', '<>', '')
            ->whereNotNull('Locationcode')
            ->where('Locationcode', '<>', '');
    }

    public function scopeWithGst($query)
    {
        return $query
            ->whereNotNull('GST')
            ->where('GST', '<>', '');
    }

    public function scopeWithColor($query)
    {
        return $query
            ->whereNotNull('Color')
            ->where('Color', '<>', '');
    }

    public function scopeByGst($query, $gst)
    {
        return $query->where('GST', $gst);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('Locationcode', $code);
    }

    public function scopeSearch($query, $searchQuery)
    {
        return $query->where(function ($q) use ($searchQuery) {

            $q->where('LocationName', 'LIKE', "%{$searchQuery}%")
              ->orWhere('Locationcode', 'LIKE', "%{$searchQuery}%")
              ->orWhere('LocationAddress', 'LIKE', "%{$searchQuery}%");

        });
    }

    public function scopeOrderByName($query)
    {
        return $query->orderBy('LocationName', 'ASC');
    }

    public function scopeOrderByCode($query)
    {
        return $query->orderBy('Locationcode', 'ASC');
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    public static function findByCode($code)
    {
        return self::where('Locationcode', $code)->first();
    }

    public static function getAllCodes()
    {
        return self::whereNotNull('Locationcode')
            ->where('Locationcode', '<>', '')
            ->pluck('Locationcode')
            ->toArray();
    }

    public static function getWithGst()
    {
        return self::withGst()
            ->orderByName()
            ->get();
    }

    public static function getWithColor()
    {
        return self::withColor()
            ->orderByName()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Helpers
    |--------------------------------------------------------------------------
    */

    public function isUsedInStock()
    {
        return DB::table('HSStock')
            ->where(function ($query) {
                $query->where('LOCATION', $this->LocationID)
                      ->orWhere('LOCATION', $this->Locationcode);
            })
            ->exists();
    }

    public function stockItemCount()
    {
        return DB::table('HSStock')
            ->where(function ($query) {
                $query->where('LOCATION', $this->LocationID)
                      ->orWhere('LOCATION', $this->Locationcode);
            })
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | API / Array Response
    |--------------------------------------------------------------------------
    */

    public function toArray()
    {
        return [
            'LocationID'       => $this->LocationID,
            'Locationcode'     => $this->Locationcode,
            'LocationName'     => $this->LocationName,
            'LocationAddress'  => $this->LocationAddress,
            'Color'            => $this->Color,
            'GST'              => $this->GST,
            'QRCODE'           => $this->QRCODE,

            'GSTPercentage'    => $this->gst_percentage,
            'HasGst'           => $this->has_gst,
            'HasColor'         => $this->has_color,
            'FullName'         => $this->full_name,
        ];
    }
}
