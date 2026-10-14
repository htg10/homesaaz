<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GRN extends Model
{
    protected $table = 'GoodsReceived';

    public $timestamps = false;

    protected $fillable = [
        'GRNID',
        'GmNo',
        'GrnDate',
        'GmAmount',
        'Billing',
        'Supplier',
        'FloorName',
        'BillAmount',
        'TotMRP',
    ];

    protected $casts = [
        'GrnDate' => 'datetime',
        'GmAmount' => 'float',
        'BillAmount' => 'float',
        'TotMRP' => 'float',

    ];

    /**
     * Get GRN Data From Stored Procedure
     *
     * @param  string  $dateFrom
     * @param  string  $dateTo
     * @param  int  $locationID
     * @param  int  $floorID
     * @return array
     */
    public static function getGRNData(
        $dateFrom,
        $dateTo,
        $locationID,
        $floorID = 0
    ) {
        $cacheKey = 'grn.proc.' . md5(json_encode([$dateFrom, $dateTo, (int) $floorID, (int) $locationID]));

        return Cache::remember($cacheKey, 300, function () use ($dateFrom, $dateTo, $floorID, $locationID) {
            return DB::select(
                'EXEC dbo.ProcGoodsReceived
                    @DateFrom = ?,
                    @DateTo = ?,
                    @FloorID = ?,
                    @locationID = ?
                ',
                [$dateFrom, $dateTo, $floorID, $locationID]
            );
        });
    }

    /**
     * Fast SQL-side list/search path.
     *
     * Instead of fetching the complete ProcGoodsReceived result into PHP and
     * filtering it there, this method lets SQL Server filter/count/page the
     * GoodsReceived table. It auto-detects the actual column names and returns
     * null when the table does not expose the fields required for an equivalent
     * list; callers can then safely fall back to the stored-procedure flow.
     */
    public static function getFastPaginatedData(
        string $dateFrom,
        string $dateTo,
        int $locationID,
        string $search = '',
        int $start = 0,
        int $length = 50
    ): ?array {
        try {
            $columns = Cache::remember('schema.goods_received.columns', 3600, function () {
                return collect(DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'GoodsReceived'"))
                    ->pluck('COLUMN_NAME')
                    ->filter()
                    ->values()
                    ->all();
            });

            if (empty($columns)) {
                return null;
            }

            $byLower = [];
            foreach ($columns as $column) {
                $byLower[strtolower($column)] = $column;
            }

            $pick = static function (array $candidates) use ($byLower) {
                foreach ($candidates as $candidate) {
                    $key = strtolower($candidate);
                    if (isset($byLower[$key])) {
                        return $byLower[$key];
                    }
                }
                return null;
            };

            $idCol = $pick(['GRNID', 'GrnID', 'gmID', 'GMID']);
            $dateCol = $pick(['GrnDate', 'GRNDate', 'EntryDate', 'Date']);
            $locationCol = $pick(['LocationID', 'locationID', 'LocationId', 'LocID']);

            // Location is part of the existing business scenario; if it cannot
            // be applied directly, use the old procedure instead of risking
            // cross-location data.
            if (!$idCol || !$dateCol || !$locationCol) {
                return null;
            }

            $fieldMap = [
                'GRNID' => $idCol,
                'GmNo' => $pick(['GmNo', 'GRNNo', 'GrnNo', 'EntryNo']),
                'GrnDate' => $dateCol,
                'GmAmount' => $pick(['GmAmount', 'GrnAmount', 'GRNAmount', 'GmAmt', 'Amount']),
                'Billing' => $pick(['Billing', 'BillNo', 'Billno']),
                'Supplier' => $pick(['Supplier', 'SupplierName', 'AccName']),
                'FloorName' => $pick(['FloorName', 'Floor', 'FloorDescription']),
                'BillAmount' => $pick(['BillAmount', 'BillAmt']),
                'TotMRP' => $pick(['TotMRP', 'TotalMRP']),
            ];

            $base = DB::table('GoodsReceived as G')
                ->where('G.' . $locationCol, $locationID)
                ->whereBetween('G.' . $dateCol, [$dateFrom, $dateTo]);

            $totalCacheKey = 'goods_received.total.' . md5(json_encode([$dateFrom, $dateTo, $locationID]));
            $recordsTotal = Cache::remember($totalCacheKey, 120, fn () => (clone $base)->count());

            $search = trim($search);
            if ($search !== '') {
                $searchColumns = array_values(array_filter([
                    $fieldMap['GmNo'],
                    $fieldMap['Supplier'],
                    $fieldMap['FloorName'],
                    $fieldMap['Billing'],
                ]));

                if (!empty($searchColumns)) {
                    $base->where(function ($q) use ($searchColumns, $search) {
                        foreach ($searchColumns as $i => $column) {
                            if ($i === 0) {
                                $q->where('G.' . $column, 'LIKE', '%' . $search . '%');
                            } else {
                                $q->orWhere('G.' . $column, 'LIKE', '%' . $search . '%');
                            }
                        }
                    });
                }
            }

            $selects = [];
            foreach ($fieldMap as $alias => $column) {
                if ($column) {
                    $selects[] = DB::raw('G.[' . str_replace(']', ']]', $column) . '] AS [' . $alias . ']');
                }
            }
            // One SQL scan returns both the page and the filtered count.
            $selects[] = DB::raw('COUNT_BIG(*) OVER() AS [__filtered_count]');

            $rows = $base
                ->select($selects)
                ->orderByDesc('G.' . $dateCol)
                ->orderByDesc('G.' . $idCol)
                ->offset(max(0, $start))
                ->limit(min(max($length, 1), 100))
                ->get()
                ->all();

            $recordsFiltered = $search === '' ? $recordsTotal : 0;
            if (!empty($rows)) {
                $recordsFiltered = (int) ($rows[0]->__filtered_count ?? $recordsFiltered);
                foreach ($rows as $row) {
                    unset($row->__filtered_count);
                }
            }

            return [
                'data' => $rows,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
            ];
        } catch (\Throwable $e) {
            Log::warning('GoodsReceived fast pagination unavailable; using stored-procedure fallback.', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public static function findGRN($id)
    {
        $data = self::getGRNData(
            now()->startOfMonth()->format('Y-m-d H:i:s'),
            now()->endOfMonth()->format('Y-m-d H:i:s'), 8, 0);

        return collect($data)->first(function ($item) use ($id) {
            return ($item->GRNID ?? null) == $id;
        });
    }

    /**
     * @param  int  $grnId
     * @param  int  $locationID (REQUIRED by stored procedure - default 8)
     * @return array
     */

    // public static function getGRNItemData($grnId, $locationID = 8)
    // {
    //     try {

    //         $items = DB::select(
    //             'EXEC dbo.ProcGrndetDetails @grnID = ?, @locationID = ?',
    //             [$grnId, $locationID]
    //         );

    //         return collect($items)->map(function ($item) {

    //             return (object) [
    //                 'ItemImage' => self::cleanUTF8($item->ItemImage ?? null),
    //                 'ItemCode' => self::cleanUTF8($item->ItemCode ?? null),
    //                 'CompanyName' => self::cleanUTF8($item->COMPANYNAME ?? null),
    //                 'ItemName' => self::cleanUTF8($item->ITEMNAME ?? null),
    //                 'DesignName' => self::cleanUTF8($item->DESIGNNAME ?? null),
    //                 'Color' => self::cleanUTF8($item->COLOR ?? null),
    //                 'Size' => self::cleanUTF8($item->SIZE ?? null),
    //                 'Unit' => self::cleanUTF8($item->UNIT ?? null),
    //                 'QualityName' => self::cleanUTF8($item->QualityName ?? null),
    //                 'SrNo' => $item->Smo ?? null,
    //                 'Qty' => $item->Qty ?? 0,
    //                 'CostPrice' => $item->Costprice ?? 0,
    //                 'Amount' => $item->AMOUNT ?? 0,
    //                 'ImageUrl' => self::cleanUTF8($item->ItemImage ?? null),
    //                 'TaxRate' => $item->TAXRATE ?? 0,
    //                 'SupplierName' => self::cleanUTF8($item->Supplier ?? null),

    //             ];
    //         })->toArray();
    //     } catch (\Exception $e) {
    //         Log::error(
    //             'GRN Item Data Error: '.$e->getMessage()
    //         );
    //         return [];

    //     }
    // }
    public static function getGRNItemData($grnId, $locationID)
    {
        try {

            $grnId = (int) $grnId;
            $locationID = (int) $locationID;
            $items = Cache::remember(
                'grn.items.' . $locationID . '.' . $grnId,
                300,
                fn () => DB::select(
                    'EXEC dbo.ProcGrnDetDetails
                        @grnID = ?,
                        @locationID = ?',
                    [$grnId, $locationID]
                )
            );
            return collect($items)->map(function ($item) {
                return (object) [
                    'ItemImage' => self::cleanUTF8($item->ItemImage ?? null),
                    'ItemCode' => self::cleanUTF8($item->ItemCode ?? null),
                    'CompanyName' => self::cleanUTF8($item->COMPANYNAME ?? $item->CompanyName ?? null),
                    'ItemName' => self::cleanUTF8($item->ITEMNAME ?? $item->ItemName ?? null),
                    'DesignName' => self::cleanUTF8($item->DESIGNNAME ?? $item->DesignName ?? null),
                    'Color' => self::cleanUTF8($item->COLOR ?? $item->Color ?? null),
                    'Size' => self::cleanUTF8($item->SIZE ?? $item->Size ?? null),
                    'Unit' => self::cleanUTF8($item->UNIT ?? $item->Unit ?? null),
                    'QualityName' => self::cleanUTF8($item->QualityName ?? null),
                    'SrNo' =>$item->Smo ?? $item->SRollNo?? null,
                    'Qty' => (float) ($item->Qty ?? 0),

                    'CostPrice' => (float) (
                        $item->Costprice
                        ?? $item->CostPrice
                        ?? 0
                    ),

                    'Amount' => (float) (
                        $item->AMOUNT
                        ?? $item->Amount
                        ?? 0
                    ),

                    'MRP' => (float) (
                        $item->MRP ?? 0
                    ),

                    'TaxRate' => (float) (
                        $item->TAXRATE
                        ?? $item->TaxRate
                        ?? 0
                    ),

                    'SupplierName' => self::cleanUTF8(
                        $item->Supplier
                        ?? $item->SupplierName
                        ?? null
                    ),

                    /*
                    * Extra fields agar kisi location
                    * ke procedure se aaye.
                    */
                    'AccName' => self::cleanUTF8(
                        $item->AccName ?? null
                    ),

                    'RollNo' => self::cleanUTF8(
                        $item->RollNo ?? null
                    ),

                    'DocNo' =>
                        $item->DocNo ?? null,

                    'EntryDate' =>
                        $item->EntryDate ?? null,

                    'GBillNo' =>
                        $item->GBillNo ?? null,

                    'ChallanNo' =>
                        $item->ChallanNo ?? null,

                    'GrnID' =>
                        $item->GrnID
                        ?? $item->GRNID
                        ?? null,

                    'Checker' => self::cleanUTF8(
                        $item->Checker ?? null),
                ];

            })->toArray();

        } catch (\Throwable $e) {

            Log::error('GRN Item Data Error', [
                'grnId' => $grnId,
                'locationID' => $locationID,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private static function cleanUTF8($value)
    {
        if ($value === null) {
            return null;
        }
        $value = mb_convert_encoding(
            $value,
            'UTF-8',
            'UTF-8'
        );
        return iconv(
            'UTF-8',
            'UTF-8//IGNORE',
            $value
        );

    }
}