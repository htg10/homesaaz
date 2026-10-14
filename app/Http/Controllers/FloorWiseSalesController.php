<?php

namespace App\Http\Controllers;

use App\Services\FloorWiseSalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FloorWiseSalesController extends Controller
{
    public function list()
    {
        $locations = FloorWiseSalesService::locations();
        return view('floorWiseSales.list', compact('locations'));
    }

    /**
     * AJAX: ?locationID=8&dateFrom=2026-09-25&dateTo=2026-09-25
     */
    public function getData(Request $request)
    {
        $locationID = (int) $request->input('locationID');
        if (!$locationID) {
            return response()->json(['success' => false, 'message' => 'Please select a location.'], 422);
        }

        try {
            $result = FloorWiseSalesService::report(
                $locationID,
                $request->input('dateFrom') ?: now()->format('Y-m-d'),
                $request->input('dateTo') ?: now()->format('Y-m-d')
            );

            return response()->json(['success' => true] + $result);
        } catch (\Illuminate\Database\QueryException $e) {
            // SQL errors: log the details, show a simple message
            Log::error('Floor Wise Sales Error', ['locationID' => $locationID, 'error' => $e->getMessage()]);
            $message = config('app.debug')
                ? 'SQL Error: ' . mb_substr($e->getMessage(), 0, 400)
                : 'Unable to load Floor Wise Sales for this location. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 500);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Floor Wise Sales Error', ['locationID' => $locationID, 'error' => $e->getMessage()]);
            // In local/debug mode show the real reason, on live keep it simple
            $message = config('app.debug')
                ? 'Error: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
                : 'Unable to load Floor Wise Sales. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 500);
        }
    }
}