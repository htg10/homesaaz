<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class HomeStayController extends Controller
{
    public function homeStay()
    {
        return response()->json([
            'status' => true,
            'message' => 'Home Stay modules fetched successfully',
            'data' => [
                [
                    'title' => 'Pending Rent',
                    'key' => 'pending_rent'
                ],
                [
                    'title' => 'Student Details',
                    'key' => 'student_details'
                ],
                [
                    'title' => 'Bed Status',
                    'key' => 'bed_status'
                ],
                [
                    'title' => 'New Student',
                    'key' => 'new_student'
                ]
            ]
        ]);
    }
}