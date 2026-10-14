<?php

namespace App\Http\Controllers;

use App\Models\DailyCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyCollectionController extends Controller
{
    /**
     * Daily Collection list page.
     */
    public function list()
    {
        $title = 'Daily Collection';
        return view('dailyCollection.list', compact('title'));
    }

    /**
     * Data for the list page (AJAX).
     */
    public function getData(Request $request)
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
            Log::error('Daily Collection Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load Daily Collection data.',
            ], 500);
        }
    }
}