<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Daily Collection - location-wise collection summary.
 * Table: dbo.ALLLOCATIONCOLLECTION (read-only for this app).
 */
class DailyCollection extends Model
{
    protected $table = 'ALLLOCATIONCOLLECTION';
    protected $primaryKey = 'AllLocCollectionHSID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'Location',
        'Cash',
        'CreditCard',
        'Cheque',
        'TAmount',
        'DiscPCT',
        'Aorder',
    ];

    protected $casts = [
        'AllLocCollectionHSID' => 'integer',
        'Cash' => 'float',
        'CreditCard' => 'float',
        'Cheque' => 'float',
        'TAmount' => 'float',
        'DiscPCT' => 'float',
        'Aorder' => 'integer',
    ];

    /**
     * All locations in display order (Aorder, then Location) + grand totals.
     *
     * @return array{data: array<int, array>, totals: array}
     */
    public static function getCollectionList(?string $search = null): array
    {
        $query = self::query()
            ->select([
                'AllLocCollectionHSID',
                'Location',
                'Cash',
                'CreditCard',
                'Cheque',
                'TAmount',
                'DiscPCT',
                'Aorder',
            ])
            ->orderBy('Aorder')
            ->orderBy('Location');

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where('Location', 'like', '%' . $search . '%');
        }

        $rows = $query->get();

        $data = [];
        $totals = [
            'Cash' => 0.0,
            'CreditCard' => 0.0,
            'Cheque' => 0.0,
            'TAmount' => 0.0,
        ];

        // Location colours from the Location table (live), matched by Locationcode.
        // "LJP-GF" uses the colour of "LJP" when it has no own code.
        $colorByCode = [];
        $nameByCode = [];
        foreach (\App\Services\UserAccessService::allLocations() as $loc) {
            if ($loc['code'] !== '') {
                $colorByCode[strtoupper($loc['code'])] = $loc['color'] ?? '#9CA3AF';
                $nameByCode[strtoupper($loc['code'])] = $loc['name'];
            }
        }

        $srNo = 0;
        foreach ($rows as $row) {
            // Only locations this user is allowed to see (admin sees all)
            if (!\App\Services\UserAccessService::canLocationCode($row->Location ?? '')) {
                continue;
            }

            $item = [
                'SrNo' => ++$srNo,
                'AllLocCollectionHSID' => (int) $row->AllLocCollectionHSID,
                'Location' => trim((string) ($row->Location ?? '')) ?: '-',
                'LocationColor' => self::lookupByCode($row->Location ?? '', $colorByCode) ?? '#9CA3AF',
                'LocationName' => self::lookupByCode($row->Location ?? '', $nameByCode),
                'Cash' => round((float) ($row->Cash ?? 0), 2),
                'CreditCard' => round((float) ($row->CreditCard ?? 0), 2),
                'Cheque' => round((float) ($row->Cheque ?? 0), 2),
                'TAmount' => round((float) ($row->TAmount ?? 0), 2),
                'DiscPCT' => round((float) ($row->DiscPCT ?? 0), 2),
                'Aorder' => (int) ($row->Aorder ?? 0),
            ];

            $totals['Cash'] += $item['Cash'];
            $totals['CreditCard'] += $item['CreditCard'];
            $totals['Cheque'] += $item['Cheque'];
            $totals['TAmount'] += $item['TAmount'];

            $data[] = $item;
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, 2);
        }
        $totals['Locations'] = count($data);

        return [
            'data' => $data,
            'totals' => $totals,
        ];
    }

    /** Value for a code like "LJP" or "LJP-GF" (falls back to the part before "-"). */
    private static function lookupByCode(?string $value, array $map): ?string
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return null;
        }
        if (isset($map[$value])) {
            return $map[$value];
        }
        $base = trim(explode('-', $value)[0]);
        return $map[$base] ?? null;
    }
}