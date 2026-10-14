<?php

namespace App\Models;

use App\Services\UserAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Attendance - employee attendance summary.
 * Table: dbo.EmployeeStatus (read-only for this app).
 *   Today = 1 -> present today, 0 -> not present today
 */
class Attendance extends Model
{
    protected $table = 'EmployeeStatus';
    protected $primaryKey = 'EmployeeId';
    public $incrementing = false;
    public $timestamps = false;

    /** Columns used by the list (image columns are left out on purpose). */
    public const LIST_COLUMNS = [
        'EmployeeId',
        'EmployeeCode',
        'EmployeeName',
        'MobileNo',
        'LocationID',
        'Today',
        'TotPresent',
        'TotAbsent',
        'TotLate',
        'TotHalfday',
        'sysEmployeeID',
    ];

    /**
     * Base query with all filters + location permission applied.
     *
     * $filters: locationID (int|null), search (name / phone / code), today ('present'|'absent'|'late'|'all')
     */
    private static function filteredQuery(array $filters)
    {
        $query = self::query();

        // Location permission (admin = all)
        $allowed = UserAccessService::allowedLocationIds();
        if ($allowed !== null) {
            $query->whereIn('LocationID', $allowed ?: [0]);
        }

        if (!empty($filters['locationID'])) {
            $query->where('LocationID', (int) $filters['locationID']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $digits = preg_replace('/\D/', '', $search);

            $query->where(function ($q) use ($search, $digits) {
                $q->where('EmployeeName', 'like', '%' . $search . '%')
                    ->orWhere('EmployeeCode', 'like', '%' . $search . '%');

                // Phone: match digits only (so "87008 77245" also works)
                if (strlen($digits) >= 3) {
                    $q->orWhere('MobileNo', 'like', '%' . $digits . '%');
                }
            });
        }

        // Attendance filter: present (default) | absent | late | all
        $today = strtolower((string) ($filters['today'] ?? 'present'));
        if ($today === 'present') {
            $query->where('Today', 1);
        } elseif ($today === 'absent') {
            $query->where(function ($q) {
                $q->where('Today', 0)->orWhereNull('Today');
            });
        } elseif ($today === 'late') {
            // Employees who have come late (TotLate > 0)
            $query->where('TotLate', '>', 0);
        }

        return $query;
    }

    /**
     * Paginated list + summary for the current filters.
     */
    public static function getList(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(max(1, $perPage), 100);

        $locationNames = [];
        $locationColors = [];
        foreach (UserAccessService::allLocations() as $loc) {
            $locationNames[$loc['id']] = $loc['name'];
            $locationColors[$loc['id']] = $loc['color'] ?? '#9CA3AF';
        }

        // Summary ignores the "today" filter so the cards always show the full picture
        $summaryFilters = $filters;
        $summaryFilters['today'] = 'all';
        $summaryRow = self::filteredQuery($summaryFilters)
            ->selectRaw('COUNT(*) AS Total,
                SUM(CASE WHEN Today = 1 THEN 1 ELSE 0 END) AS PresentToday,
                SUM(CASE WHEN TotLate > 0 THEN 1 ELSE 0 END) AS LateCount')
            ->first();

        $total = (int) ($summaryRow->Total ?? 0);
        $presentToday = (int) ($summaryRow->PresentToday ?? 0);
        $lateCount = (int) ($summaryRow->LateCount ?? 0);

        $query = self::filteredQuery($filters);
        $recordsFiltered = (clone $query)->count();

        // Pagination with ROW_NUMBER(): works on SQL Server 2008 too
        // (OFFSET ... FETCH, used by skip()/take(), needs SQL Server 2012+).
        $inner = $query
            ->select(self::LIST_COLUMNS)
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY EmployeeName, EmployeeId) AS RowNum')
            ->toBase();

        $from = ($page - 1) * $perPage + 1;
        $to = $page * $perPage;

        $rows = DB::table(DB::raw('(' . $inner->toSql() . ') AS emp'))
            ->mergeBindings($inner)
            ->whereBetween('RowNum', [$from, $to])
            ->orderBy('RowNum')
            ->get();

        $data = [];
        foreach ($rows as $i => $row) {
            $locationId = (int) $row->LocationID;

            $data[] = [
                'SrNo' => ($page - 1) * $perPage + $i + 1,
                'EmployeeId' => (int) $row->EmployeeId,
                'EmployeeCode' => trim((string) $row->EmployeeCode),
                'EmployeeName' => trim((string) $row->EmployeeName),
                'MobileNo' => trim((string) $row->MobileNo),
                'LocationID' => $locationId,
                'LocationName' => $locationNames[$locationId] ?? ('Location ' . $locationId),
                'LocationColor' => $locationColors[$locationId] ?? '#9CA3AF',
                'Today' => (int) $row->Today === 1 ? 1 : 0,
                'TotPresent' => (int) $row->TotPresent,
                'TotAbsent' => (int) $row->TotAbsent,
                'TotLate' => (int) $row->TotLate,
                'TotHalfday' => (int) $row->TotHalfday,
            ];
        }

        return [
            'data' => $data,
            'summary' => [
                'total' => $total,
                'presentToday' => $presentToday,
                'absentToday' => max(0, $total - $presentToday),
                'late' => $lateCount,
            ],
            'recordsFiltered' => $recordsFiltered,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) max(1, ceil($recordsFiltered / $perPage)),
        ];
    }

    /** Locations for the dropdown (only allowed ones). */
    public static function locationOptions(): array
    {
        $ids = UserAccessService::allowedLocationIds();

        return array_values(array_filter(
            UserAccessService::allLocations(),
            fn ($l) => $ids === null || in_array($l['id'], $ids, true)
        ));
    }
}