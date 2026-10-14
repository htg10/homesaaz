<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DailyCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyCollectionController extends Controller
{
    /**
     * GET /api/daily-collection?search=LJP
     * Location-wise collection with grand totals.
     */
    public function index(Request $request)
    {
        try {
            $result = DailyCollection::getCollectionList($request->input('search'));

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'totals' => $result['totals'],
                'updatedAt' => now()->format('d-m-Y h:i A'),
            ]);
        } catch (\Throwable $e) {
            Log::error('API Daily Collection Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load Daily Collection data.',
            ], 500);
        }
    }
}