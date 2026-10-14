<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HSStock extends Model
{
    protected $table = 'HSStock';
    protected $primaryKey = 'ItemDetID';
    public $timestamps = false;

    protected $fillable = [
        'ItemCode',
        'CompanyName',
        'ItemName',
        'ProductName',
        'DesignName',
        'Color',
        'StKQty',
        'MRP',
        'Rate',
        'SectionName',
        'HsStockID',
        'Unit',
        'Size',
        'LJP',
        'FBD',
        'RGN',
        'LS',
        'MH',
        'GGN',
        'RJC',
        'ACost',
        'AccountID',
        'WHQTY',
        'MD',
        'KAVERI',
        'COLLECTIONNAME',
        'HSNCODE',
        'GST',
        'DPEX',
        'MU',
        'RJCACCOUNTID',
    ];

    protected $casts = [
        'StKQty' => 'float',
        'MRP' => 'float',
        'Rate' => 'float',
        'LJP' => 'float',
        'FBD' => 'float',
        'ACost' => 'float',
        'WHQTY' => 'float',
        'GST' => 'float',
        'DPEX' => 'float',
    ];

    public function itemDet()
    {
        return $this->hasOne(ItemDet::class, 'ItemDetID', 'ItemDetID');
    }

    public function getSupplierNameAttribute()
    {
        return $this->itemDet?->SupplierName ?? null;
    }

    public function scopeWithUserScopes(Builder $query, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if ($user && $user->roles()->where('name', 'admin')->exists()) {
            return $query;
        }

        if ($user) {
            $sectionRestrictions = $user->sectionRestrictions()->get();
            if ($sectionRestrictions->isNotEmpty()) {
                $sections = [];
                foreach ($sectionRestrictions as $restriction) {
                    if ($restriction->section === 'Section' || $restriction->section === 'SectionName') {
                        $sections[] = $restriction->section_value;
                    }
                }
                if (!empty($sections)) {
                    $query->whereIn('SectionName', $sections);
                }
            }

            $companyRestrictions = $user->companyRestrictions()->pluck('company_name')->toArray();
            if (!empty($companyRestrictions)) {
                $query->whereIn('CompanyName', $companyRestrictions);
            }
        }

        return $query;
    }

    public function scopeByItemCode(Builder $query, $itemCode)
    {
        return $query->where('ItemCode', $itemCode);
    }

    public function scopeByCompany(Builder $query, $company)
    {
        return $query->where('CompanyName', $company);
    }

    public function scopeBySection(Builder $query, $section)
    {
        return $query->where('SectionName', $section);
    }

    public function scopeLowStock(Builder $query, $threshold = 10)
    {
        return $query->where('StKQty', '<=', $threshold);
    }

    public function scopeRecent(Builder $query)
    {
        return $query->orderByDesc('ItemDetID');
    }
}