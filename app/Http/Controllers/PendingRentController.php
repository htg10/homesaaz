<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendingRent;
use Illuminate\Support\Facades\DB;

class PendingRentController extends Controller
{
    public function list()
    {
        $title = "Pending Rent List";
        return view('PendingRent.list', compact('title'));
    }

    public function getDataTable(Request $request)
    {
        try {
            $whereClause = '';
            if ($request->filled('search.value')) {
                $search = str_replace("'", "''", trim($request->input('search.value')));
                $whereClause = "
                WHERE Name LIKE '%{$search}%'
                OR RoomNo LIKE '%{$search}%'
                OR LocationName LIKE '%{$search}%'
                OR CAST(RentAmount AS VARCHAR) LIKE '%{$search}%'
                OR CAST(BalAmount AS VARCHAR) LIKE '%{$search}%'
            ";
            }

            $total = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM PendingRent
            {$whereClause}")->total;
            $start  = (int) ($request->start ?? 0);
            $length = (int) ($request->length ?? 10);
            $end    = $start + $length;
            $data = DB::select("
            SELECT *
            FROM (
                SELECT
                    ROW_NUMBER() OVER (ORDER BY RentPendingID DESC) AS RowNum,
                    RentPendingID,
                    Name,
                    RoomNo,
                    LocationName,
                    RentAmount,
                    RecAmount,
                    BalAmount,
                    RentDate,
                    StudentImage
                FROM PendingRent
                {$whereClause}
            ) AS T
            WHERE RowNum > {$start}
              AND RowNum <= {$end}
            ORDER BY RowNum
        ");
            $rows = [];
            foreach ($data as $key => $row) {
                $rows[] = [
                    'DT_RowIndex'   => $start + $key + 1,
                    'RentPendingID' => $row->RentPendingID,
                    'Name'          => $row->Name ?? '-',
                    'RoomNo'        => $row->RoomNo ?? '-',
                    'LocationName'  => $row->LocationName ?? '-',
                    'RentAmount'    => (float) ($row->RentAmount ?? 0),
                    'RecAmount'     => (float) ($row->RecAmount ?? 0),
                    'BalAmount'     => (float) ($row->BalAmount ?? 0),
                    'RentDate'      => $row->RentDate,
                    'StudentImage'  => $row->StudentImage,
                ];
            }
            return response()->json([
                'draw' => (int) ($request->draw ?? 1),
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $rows,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'draw' => (int) ($request->draw ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
