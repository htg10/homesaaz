<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentDetails;
use Illuminate\Support\Facades\DB;

class StudentDetailsController extends Controller
{
    public function list()
    {
        $title = "Student Details List";
        return view('StudentDetails.list', compact('title'));
    }

    public function getDataTable(Request $request)
    {
        try {
            $whereClause = '';
            if ($request->filled('search.value')) {
                $search = str_replace("'", "''", trim($request->input('search.value')));
                $whereClause = "
                    WHERE StudentName LIKE '%{$search}%'
                    OR RoomNo LIKE '%{$search}%'
                    OR Location LIKE '%{$search}%'
                    OR MobileNo LIKE '%{$search}%'
                    OR FatherName LIKE '%{$search}%'
                ";
            }
            $total = DB::selectOne("
                SELECT COUNT(*) AS total
                FROM [StudentDetails]
                {$whereClause}")->total;

            $start = (int)($request->start ?? 0);
            $length = (int)($request->length ?? 10);
            $end = $start + $length;
            $data = DB::select("
                SELECT *
                FROM (
                    SELECT
                        ROW_NUMBER() OVER (ORDER BY StudentdetID DESC) AS RowNum,
                        StudentdetID,
                        StudentName,
                        address,
                        FatherName,
                        RoomNo,
                        BedNo,
                        RentAmt,
                        RentDate,
                        Location,
                        StudentImage,
                        MobileNo,
                        StudentDeskTopID,
                        StartingDate,
                        EndingDate
                    FROM [StudentDetails]
                    {$whereClause}
                ) AS T
                WHERE RowNum > {$start}
                  AND RowNum <= {$end}
                ORDER BY RowNum
            ");

            $rows = [];
            foreach ($data as $key => $row) {
                $rows[] = [
                    'DT_RowIndex'      => $start + $key + 1,
                    'StudentdetID'     => $row->StudentdetID,
                    'StudentName'      => $row->StudentName ?? '-',
                    'RoomNo'           => $row->RoomNo ?? '-',
                    'BedNo'            => $row->BedNo ?? '-',
                    'Location'         => $row->Location ?? '-',
                    'RentAmt'          => floatval($row->RentAmt ?? 0),
                    'RentDate'         => $row->RentDate,
                    'MobileNo'         => $row->MobileNo ?? '-',
                    'FatherName'       => $row->FatherName ?? '-',
                    'address'          => $row->address ?? '-',
                    'StartingDate'     => $row->StartingDate,
                    'EndingDate'       => $row->EndingDate,
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