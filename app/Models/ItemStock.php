<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ItemStock extends Model
{
    public $timestamps = false;

    public static function getStockReport($itemCode)
    {
        return DB::select(
            "EXEC dbo.Online_Sp_StockReportByItemCode @ItemCode = ?",
            [$itemCode]
        );
    }
}