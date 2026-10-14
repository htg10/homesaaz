<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadGateEntryBill extends Model
{
    protected $table = 'VendorInvoices';
    
    // ✅ Use PascalCase column names for timestamps
    const CREATED_AT = 'CreatedDate';
    const UPDATED_AT = 'UpdatedDate';
    
    protected $primaryKey = 'Id';
    
    protected $fillable = [
        'InvoiceNumber',
        'ImagePath',
        'ApprovedByAdminID',
        'ApprovalStatus',
        'ApprovedAt',
        'ApprovalNotes'
    ];

    protected $casts = [
        'ApprovedAt' => 'datetime',
        'CreatedDate' => 'datetime',
        'UpdatedDate' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->InvoiceNumber)) {
                $model->InvoiceNumber = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber()
    {
        $last = self::where('InvoiceNumber', 'LIKE', 'INV-%')
            ->orderBy('id', 'DESC')
            ->first();
        
        if (!$last) {
            return 'INV-001';
        }
        
        $num = (int) substr($last->InvoiceNumber, 4);
        return 'INV-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}