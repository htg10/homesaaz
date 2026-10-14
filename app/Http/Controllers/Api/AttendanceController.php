<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * GET /api/attendance?locationID=8&search=ram&today=present&page=1&perPage=20
     *   search : employee name, mobile number or employee code
     *   today  : present (default) | absent | late | all
     */
    public function index(Request $request)
    {
        try {
            $result = Attendance::getList(
                [
                    'locationID' => $request->input('locationID'),
                    'search' => $request->input('search'),
                    'today' => $request->input('today', 'present'),
                ],
                (int) $request->input('page', 1),
                (int) $request->input('perPage', 20)
            );

            return response()->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            Log::error('API Attendance Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load attendance data.',
            ], 500);
        }
    }

    /**
     * GET /api/attendance/locations - locations this user may see.
     */
    public function locations()
    {
        return response()->json([
            'success' => true,
            'data' => Attendance::locationOptions(),
        ]);
    }
}