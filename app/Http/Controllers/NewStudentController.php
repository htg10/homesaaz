<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewStudent;
use Illuminate\Support\Facades\DB;

class NewStudentController extends Controller
{
    public function list()
    {
        $title = "New Student Admission";
        return view('newStudent.list', compact('title'));
    }

    public function getDataTable(Request $request)
    {
        try {

            $whereClause = '';

            if ($request->filled('search.value')) {
                $search = str_replace("'", "''", trim($request->input('search.value')));

                $whereClause = "
                    WHERE
                        FirstName LIKE '%{$search}%'
                        OR LastName LIKE '%{$search}%'
                        OR MobileNo LIKE '%{$search}%'
                        OR FatherName LIKE '%{$search}%'
                        OR MotherName LIKE '%{$search}%'
                        OR Location LIKE '%{$search}%'
                ";
            }

            $totalResult = DB::selectOne("
                SELECT COUNT(*) AS total
                FROM StudentNewAdmission
                {$whereClause}
            ");

            $total = $totalResult->total ?? 0;

            $start  = (int)($request->start ?? 0);
            $length = (int)($request->length ?? 10);

            $data = DB::select("
                SELECT *
                FROM
                (
                    SELECT
                        ROW_NUMBER() OVER (ORDER BY NewAdmissionID DESC) AS RowNum,
                        NewAdmissionID,
                        FirstName,
                        LastName,
                        MobileNo,
                        Addarcard,
                        Vaccination,
                        AddharCardAtt,
                        Icard,
                        Location,
                        FatherName,
                        MotherName,
                        FatherMobileNo,
                        MotherMobileNo,
                        Remarks,
                        TrfToMain
                    FROM StudentNewAdmission
                    {$whereClause}
                ) T
                WHERE RowNum > {$start}
                  AND RowNum <= " . ($start + $length) . "
                ORDER BY RowNum
            ");

            $rows = [];

            foreach ($data as $key => $row) {

                $rows[] = [
                    'DT_RowIndex'      => $start + $key + 1,
                    'NewAdmissionID'   => $row->NewAdmissionID,
                    'FirstName'        => $row->FirstName,
                    'LastName'         => $row->LastName,
                    'MobileNo'         => $row->MobileNo,
                    'Addarcard'        => $row->Addarcard,
                    'Vaccination'      => $row->Vaccination,
                    'AddharCardAtt'    => $row->AddharCardAtt,
                    'Icard'            => $row->Icard,
                    'Location'         => $row->Location,
                    'FatherName'       => $row->FatherName,
                    'MotherName'       => $row->MotherName,
                    'FatherMobileNo'   => $row->FatherMobileNo,
                    'MotherMobileNo'   => $row->MotherMobileNo,
                    'Remarks'          => $row->Remarks,
                    'TrfToMain'        => $row->TrfToMain,
                ];
            }

            return response()->json([
                'draw'            => intval($request->draw ?? 1),
                'recordsTotal'    => $total,
                'recordsFiltered' => $total,
                'data'            => $rows,
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