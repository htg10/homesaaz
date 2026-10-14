<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BedOccupancy;
use Illuminate\Support\Facades\DB;

class BedOccupancyController extends Controller
{
    public function list()
    {
        $title = "Bed Occupancy";
        return view('BedOccupancy.list', compact('title'));
    }

    public function getDataTable(Request $request)
    {
        try {
            $whereClause = '';
            if ($request->filled('search.value')) {
                $search = str_replace("'", "''", trim($request->input('search.value')));
                $whereClause = "WHERE [LocationName] LIKE '%{$search}%'";
            }
            $totalResult = DB::selectOne("
                SELECT COUNT(*) AS total
                FROM [BedOccupancy]
                {$whereClause}
            ");
            $total = $totalResult->total ?? 0;

            $start = (int)($request->start ?? 0);
            $length = (int)($request->length ?? 10);
            $data = DB::select("
                SELECT *
                FROM (
                    SELECT
                        ROW_NUMBER() OVER (ORDER BY [BedOccID] DESC) AS RowNum,
                        [BedOccID],
                        [LocationName],
                        [Letout],
                        [TOTALBED]
                    FROM [BedOccupancy]
                    {$whereClause}
                ) AS T
                WHERE RowNum > {$start}
                  AND RowNum <= {$start} + {$length}
                ORDER BY RowNum
            ");

            $rows = [];
            foreach ($data as $key => $row) {
                $vacant = (intval($row->TOTALBED ?? 0) - intval($row->Letout ?? 0));
                
                $rows[] = [
                    'DT_RowIndex'      => $start + $key + 1,
                    'BedOccID'         => $row->BedOccID,
                    'LocationName'     => $row->LocationName ?? '-',
                    'Letout'           => intval($row->Letout ?? 0),
                    'Vacant'           => $vacant,
                    'TOTALBED'         => intval($row->TOTALBED ?? 0),
                ];
            }

            return response()->json([
                'draw' => intval($request->draw ?? 1),
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $rows,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'draw' => intval($request->draw ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}