<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GateEntry
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
        $cacheKey = 'gateentry.proc.' . md5(json_encode([$dateFrom, $dateTo, (int) $floorID, (int) $locationID]));

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
     * Get item-wise (line item) data for a single GRN.
     *
     * Calls ProcGrndetDetails which returns columns:
     * ItemImage, ItemCode, COMPANYNAME, ITEMNAME, DESIGNNAME, COLOR,
     * SIZE, UNIT, QualityName, Smo, Qty, Costprice, AMOUNT, MRP,
     * TAXRATE, Supplier
     *
     * @param  int  $grnId
     * @return array
     */
    public static function getGRNItemData($grnId)
    {
        try {

            $items = Cache::remember(
                'gateentry.items.' . (int) $grnId,
                300,
                fn () => DB::select(
                    'EXEC dbo.ProcGrndetDetails @grnID = ?',
                    [(int) $grnId]
                )
            );

            return collect($items)->map(function ($item) {

                return (object) [
                    'ItemImage' => self::cleanUTF8($item->ItemImage ?? null),
                    'ItemCode' => self::cleanUTF8($item->ItemCode ?? null),
                    'CompanyName' => self::cleanUTF8($item->COMPANYNAME ?? null),
                    'ItemName' => self::cleanUTF8($item->ITEMNAME ?? null),
                    'DesignName' => self::cleanUTF8($item->DESIGNNAME ?? null),
                    'Color' => self::cleanUTF8($item->COLOR ?? null),
                    'Size' => self::cleanUTF8($item->SIZE ?? null),
                    'Unit' => self::cleanUTF8($item->UNIT ?? null),
                    'QualityName' => self::cleanUTF8($item->QualityName ?? null),
                    'SrNo' => $item->Smo ?? null,
                    'Qty' => $item->Qty ?? 0,
                    'CostPrice' => $item->Costprice ?? 0,
                    'Amount' => $item->AMOUNT ?? 0,
                    'ImageUrl' => self::cleanUTF8($item->ItemImage ?? null),
                    'TaxRate' => $item->TAXRATE ?? 0,
                    'SupplierName' => self::cleanUTF8($item->Supplier ?? null),

                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error(
                'GRN Item Data Error: '.$e->getMessage()
            );
            return [];

        }
    }

    /* =====================================================================
     |  WH GOODS DETAILS (dbo.ProcWHGoodsDetails)
     |  Gate Entry list is now driven by this stored procedure. Every column
     |  the procedure returns is passed through to the list as-is, so if the
     |  procedure gains/loses columns the UI adapts automatically.
     * ===================================================================== */

    public const WH_GOODS_PROC = 'dbo.ProcWHGoodsDetails';

    /**
     * Read the stored procedure's parameter list from SQL Server metadata.
     * Cached for 1 hour so the metadata query does not run on every request.
     *
     * @return array<int, array{name:string,type:string}>
     */
    public static function getWHGoodsProcParams(): array
    {
        return Cache::remember('gateentry.whgoods.params', 86400, function () {
            $exists = DB::selectOne(
                "SELECT OBJECT_ID(?, 'P') AS ObjId",
                [self::WH_GOODS_PROC]
            );

            if (empty($exists) || empty($exists->ObjId)) {
                throw new \RuntimeException('Stored procedure ' . self::WH_GOODS_PROC . ' was not found in the database.');
            }

            $rows = DB::select(
                "SELECT p.name AS ParamName, TYPE_NAME(p.user_type_id) AS TypeName
                   FROM sys.parameters p
                  WHERE p.object_id = OBJECT_ID(?, 'P')
                  ORDER BY p.parameter_id",
                [self::WH_GOODS_PROC]
            );

            return array_map(fn ($r) => [
                'name' => (string) $r->ParamName,
                'type' => strtolower((string) $r->TypeName),
            ], $rows);
        });
    }

    /**
     * Decide which value goes into which procedure parameter, based on the
     * parameter's name. Returns [bindings, flags].
     */
    private static function mapWHGoodsParams(array $params, int $locationID, string $dateFrom, string $dateTo): array
    {
        $bindings = [];
        $flags = ['location' => false, 'dateFrom' => false, 'dateTo' => false];
        $dateTypes = ['date', 'datetime', 'datetime2', 'smalldatetime', 'datetimeoffset', 'varchar', 'nvarchar', 'char', 'nchar'];

        foreach ($params as $param) {
            $name = $param['name'];
            if (!preg_match('/^@[A-Za-z0-9_]+$/', $name)) {
                continue; // never build SQL from an unexpected name
            }
            $key = strtolower(ltrim($name, '@'));
            $isDateType = in_array($param['type'], $dateTypes, true);

            if (str_contains($key, 'location') || $key === 'locid' || $key === 'loc') {
                $bindings[$name] = $locationID;
                $flags['location'] = true;
            } elseif ($isDateType && preg_match('/from|start|^fdate$/', $key)) {
                $bindings[$name] = $dateFrom;
                $flags['dateFrom'] = true;
            } elseif ($isDateType && preg_match('/^to|dateto|todate|_to$|end|^tdate$/', $key)) {
                $bindings[$name] = $dateTo;
                $flags['dateTo'] = true;
            } elseif (str_contains($key, 'floor')) {
                $bindings[$name] = 0;
            } else {
                $bindings[$name] = null;
            }
        }

        return [$bindings, $flags];
    }

    /** Locations that the Gate Entry screen supports (also pre-warmed by the scheduler). */
    public const SUPPORTED_LOCATION_IDS = [8, 26, 29];

    /**
     * MONTH-WISE CACHE
     * ----------------
     * The procedure's time grows with the date range (location 8: 1 month = ~2s,
     * 6 years = ~8 min). So data is always fetched ONE MONTH at a time and each
     * month is cached separately:
     *   - old months   -> cached 30 days (they don't change)
     *   - current + previous month -> cached 10 min (new entries keep coming)
     * Any date range the user picks = the needed months joined + exact date filter.
     */
    public const BASE_DATE_FROM = '2015-01-01';          // oldest month to load when no date is given
    public const PAST_MONTH_TTL = 2592000;               // 30 days
    public const RECENT_MONTH_TTL = 600;                 // 10 minutes

    private static function monthCacheKey(int $locationID, string $ym): string
    {
        return 'gateentry.whgoods.month.' . $locationID . '.' . $ym;
    }

    private static function isRecentMonth(Carbon $monthStart): bool
    {
        return $monthStart->greaterThanOrEqualTo(Carbon::now()->startOfMonth()->subMonth());
    }

    /**
     * Run dbo.ProcWHGoodsDetails for one calendar month of a location.
     */
    public static function fetchWHGoodsMonth(int $locationID, Carbon $monthStart): array
    {
        $from = $monthStart->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $to = $monthStart->copy()->endOfMonth()->format('Y-m-d') . ' 23:59:59';

        $params = self::getWHGoodsProcParams();
        [$bindings, $flags] = self::mapWHGoodsParams($params, $locationID, $from, $to);

        $assignments = [];
        foreach (array_keys($bindings) as $name) {
            $assignments[] = $name . ' = ?';
        }

        $sql = 'SET NOCOUNT ON; EXEC ' . self::WH_GOODS_PROC
            . (count($assignments) ? ' ' . implode(', ', $assignments) : '');

        try {
            $rows = DB::select($sql, array_values($bindings));
        } catch (\Throwable $e) {
            // sqlsrv throws this when the procedure returns no result set
            if (str_contains($e->getMessage(), 'contains no fields')) {
                $rows = [];
            } else {
                throw $e;
            }
        }

        $rows = array_map(fn ($r) => self::cleanRow($r), $rows);

        // Procedure has no location parameter -> filter by a location column if present.
        if (!$flags['location'] && !empty($rows)) {
            $locCol = self::findColumn($rows[0], ['LocationID', 'LocationId', 'LocID']);
            if ($locCol) {
                $rows = array_values(array_filter($rows, fn ($r) => (int) ($r->{$locCol} ?? 0) === $locationID));
            }
        }

        return $rows;
    }

    /**
     * One month of rows - from cache, or from the procedure (then cached).
     */
    private static function getMonthRows(int $locationID, Carbon $monthStart, bool $forceRefresh = false): array
    {
        $key = self::monthCacheKey($locationID, $monthStart->format('Y-m'));
        $ttl = self::isRecentMonth($monthStart) ? self::RECENT_MONTH_TTL : self::PAST_MONTH_TTL;

        if (!$forceRefresh) {
            $cached = Cache::get($key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $rows = self::fetchWHGoodsMonth($locationID, $monthStart);
        Cache::put($key, $rows, $ttl);

        return $rows;
    }

    /**
     * Month starts (Carbon) covering the given range, clamped to BASE_DATE_FROM
     * and the current month.
     */
    private static function monthsInRange(string $dateFrom, string $dateTo): array
    {
        $base = Carbon::parse(self::BASE_DATE_FROM)->startOfMonth();
        $nowMonth = Carbon::now()->startOfMonth();

        $start = Carbon::parse($dateFrom)->startOfMonth();
        $end = Carbon::parse($dateTo)->startOfMonth();

        if ($start->lessThan($base)) {
            $start = $base->copy();
        }
        if ($end->greaterThan($nowMonth)) {
            $end = $nowMonth->copy();
        }

        $months = [];
        for ($m = $start->copy(); $m->lessThanOrEqualTo($end); $m->addMonth()) {
            $months[] = $m->copy();
        }

        return $months;
    }

    /**
     * One month ("YYYY-MM") of rows for the progressive list loading.
     */
    public static function getWHGoodsMonth(int $locationID, string $ym): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) {
            throw new \InvalidArgumentException('Invalid month. Use YYYY-MM.');
        }

        $month = Carbon::createFromFormat('Y-m-d', $ym . '-01')->startOfMonth();
        if ($month->greaterThan(Carbon::now()->startOfMonth())) {
            return [];
        }

        return self::getMonthRows($locationID, $month);
    }

    /**
     * Pre-load months into cache (used by the scheduler).
     * Recent months are always refreshed; older months only if missing.
     *
     * @return array{rows:int, fetched:int, months:int}
     */
    public static function warmWHGoodsCache(int $locationID): array
    {
        $rows = 0;
        $fetched = 0;
        $months = self::monthsInRange(self::BASE_DATE_FROM, Carbon::now()->format('Y-m-d'));

        foreach ($months as $month) {
            $key = self::monthCacheKey($locationID, $month->format('Y-m'));
            $force = self::isRecentMonth($month);

            if ($force || !is_array(Cache::get($key))) {
                $fetched++;
            }

            $rows += count(self::getMonthRows($locationID, $month, $force));
        }

        return ['rows' => $rows, 'fetched' => $fetched, 'months' => count($months)];
    }

    /** Backwards-compatible name used by the warm-cache command. */
    public static function refreshWHGoodsCache(int $locationID): int
    {
        return self::warmWHGoodsCache($locationID)['rows'];
    }

    private static function dateColumn($row): ?string
    {
        foreach (array_keys((array) $row) as $col) {
            if (stripos($col, 'date') !== false) {
                return $col;
            }
        }
        return null;
    }

    /**
     * Rows for a location and date range (months joined + exact date filter).
     *
     * @return array<int, object>
     */
    public static function getWHGoodsDetails(int $locationID, string $dateFrom, string $dateTo): array
    {
        $rows = [];
        foreach (self::monthsInRange($dateFrom, $dateTo) as $month) {
            foreach (self::getMonthRows($locationID, $month) as $row) {
                $rows[] = $row;
            }
        }

        if (empty($rows)) {
            return [];
        }

        $dateCol = self::dateColumn($rows[0]);
        if (!$dateCol) {
            return $rows;
        }

        $from = substr($dateFrom, 0, 10);
        $to = substr($dateTo, 0, 10);

        return array_values(array_filter($rows, function ($r) use ($dateCol, $from, $to) {
            $d = substr((string) ($r->{$dateCol} ?? ''), 0, 10);
            return $d === '' || ($d >= $from && $d <= $to);
        }));
    }

    /**
     * Find the Gate Entry row for a GRN ID from the already-cached list result.
     * Never re-runs the stored procedure (returns null when not cached), so the
     * details page stays fast.
     */
    public static function findWHGoodsRowCached(int $locationID, string $dateFrom, string $dateTo, int $grnId): ?object
    {
        try {
            // Newest month first - the clicked entry is usually recent
            foreach (array_reverse(self::monthsInRange($dateFrom, $dateTo)) as $month) {
                $rows = Cache::get(self::monthCacheKey($locationID, $month->format('Y-m')));
                if (!is_array($rows)) {
                    continue;
                }
                foreach ($rows as $row) {
                    foreach ((array) $row as $key => $value) {
                        if (in_array(strtolower(preg_replace('/[^a-z0-9]/i', '', $key)), ['grnid', 'gmid'], true)
                            && (int) $value === $grnId) {
                            return $row;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gate Entry row lookup failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Full result (no pagination) for the browser-side list. The browser
     * then does search + pagination instantly without calling the server.
     */
    public static function getWHGoodsAll(int $locationID, string $dateFrom, string $dateTo, int $maxRows = 50000): array
    {
        $rows = self::getWHGoodsDetails($locationID, $dateFrom, $dateTo);
        $total = count($rows);

        return [
            'columns' => $total ? array_keys((array) $rows[0]) : [],
            'data' => $total > $maxRows ? array_slice($rows, 0, $maxRows) : $rows,
            'recordsTotal' => $total,
            'truncated' => $total > $maxRows,
        ];
    }

    /**
     * Search + paginate the procedure output. Shared by the web and API controllers.
     */
    public static function getWHGoodsPage(
        int $locationID,
        string $dateFrom,
        string $dateTo,
        string $search = '',
        int $start = 0,
        int $length = 50
    ): array {
        $rows = self::getWHGoodsDetails($locationID, $dateFrom, $dateTo);
        $recordsTotal = count($rows);

        // Column list in the exact order returned by the procedure.
        $columns = $recordsTotal ? array_keys((array) $rows[0]) : [];

        $search = mb_strtolower(trim($search), 'UTF-8');
        if ($search !== '') {
            $rows = array_values(array_filter($rows, function ($row) use ($search) {
                foreach ((array) $row as $value) {
                    if ($value === null || !is_scalar($value)) {
                        continue;
                    }
                    if (str_contains(mb_strtolower((string) $value, 'UTF-8'), $search)) {
                        return true;
                    }
                }
                return false;
            }));
        }

        $recordsFiltered = count($rows);
        $page = array_slice($rows, max(0, $start), min(max($length, 1), 100));

        return [
            'columns' => $columns,
            'data' => array_values($page),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
        ];
    }

    private static function findColumn($row, array $candidates): ?string
    {
        $keys = array_keys((array) $row);
        foreach ($candidates as $candidate) {
            foreach ($keys as $key) {
                if (strcasecmp($key, $candidate) === 0) {
                    return $key;
                }
            }
        }
        return null;
    }

    private static function cleanRow($row)
    {
        $clean = new \stdClass();
        foreach ((array) $row as $key => $value) {
            $clean->{$key} = is_string($value) ? self::cleanUTF8($value) : $value;
        }
        return $clean;
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