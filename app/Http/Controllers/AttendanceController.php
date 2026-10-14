<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Attendance list page.
     */
    public function list()
    {
        $locations = Attendance::locationOptions();
        return view('attendance.list', compact('locations'));
    }

    /**
     * AJAX data: ?locationID=8&search=ram&today=present&page=1&perPage=20
     */
    public function getData(Request $request)
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
            Log::error('Attendance Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load attendance data.',
            ], 500);
        }
    }
}