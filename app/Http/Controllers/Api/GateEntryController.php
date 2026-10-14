<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\GateEntry;
use App\Models\GRN;
use Barryvdh\DomPDF\Facade\Pdf;

class GateEntryController extends Controller
{
    public function list()
    {
        $supportedLocationIds = [8, 26, 29];
        
        $locations = Cache::remember('gateentry.locations.supported', 1800, fn () => DB::table('Location')
            ->select(
                'LocationID',
                'LocationCode',
                'LocationName',
                'LocationAddress',
                'QRCODE',
                'Color'
            )
            ->whereIn('LocationID', $supportedLocationIds)
            ->orderBy('LocationName')
            ->get());
        // Only the locations this user is allowed to see
        $locations = \App\Services\UserAccessService::filterByLocationId($locations);

        return view('gateEntry.list', compact('locations'));
    }


    /**
     * Gate Entry datatable - data comes from dbo.ProcWHGoodsDetails (read-only).
     * Returns every column the procedure returns, plus the column list so the
     * front-end can build the table header dynamically.
     */
    public function getDataTable(Request $request)
    {
        try {
            @set_time_limit(120);

            $locationID = $request->input('locationID');
            $dateFrom = $request->input('dateFrom');
            $dateTo = $request->input('dateTo');
            $start = intval($request->input('start', 0));
            $length = min(max(intval($request->input('length', 50)), 1), 100);
            $searchValue = (string) $request->input('search.value', '');

            // LocationID zaroori hai
            if (empty($locationID)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location ID is required',
                    'columns' => [],
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                ]);
            }

            // month=YYYY-MM -> one month only (page loads months one by one, newest first)
            if ($request->filled('month')) {
                $rows = GateEntry::getWHGoodsMonth((int) $locationID, (string) $request->input('month'));

                return response()->json([
                    'success' => true,
                    'month' => $request->input('month'),
                    'columns' => $rows ? array_keys((array) $rows[0]) : [],
                    'data' => $rows,
                    'recordsTotal' => count($rows),
                    'recordsFiltered' => count($rows),
                ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            }

            // Date empty ho to wide range use karo (pehle jaisa behaviour)
            $dateFrom = !empty($dateFrom)
                ? Carbon::parse($dateFrom)->startOfDay()->format('Y-m-d H:i:s')
                : Carbon::now()->subYears(10)->startOfDay()->format('Y-m-d H:i:s');
            $dateTo = !empty($dateTo)
                ? Carbon::parse($dateTo)->endOfDay()->format('Y-m-d H:i:s')
                : Carbon::now()->addYears(5)->endOfDay()->format('Y-m-d H:i:s');

            // all=1 -> send the complete result once; the page does search and
            // pagination in the browser (no server round-trip per keystroke/page).
            if ($request->boolean('all')) {
                $full = GateEntry::getWHGoodsAll((int) $locationID, $dateFrom, $dateTo);

                return response()->json([
                    'success' => true,
                    'columns' => $full['columns'],
                    'data' => $full['data'],
                    'recordsTotal' => $full['recordsTotal'],
                    'recordsFiltered' => $full['recordsTotal'],
                    'truncated' => $full['truncated'],
                ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            }

            $page = GateEntry::getWHGoodsPage(
                (int) $locationID,
                $dateFrom,
                $dateTo,
                $searchValue,
                $start,
                $length
            );

            return response()->json([
                'success' => true,
                'columns' => $page['columns'],
                'data' => $page['data'],
                'recordsTotal' => $page['recordsTotal'],
                'recordsFiltered' => $page['recordsFiltered'],
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        catch(\Throwable $e){
            Log::error(
                'Gate Entry Datatable Error (ProcWHGoodsDetails)',
                [
                    'message' => $e->getMessage(),
                    'locationID' => $request->input('locationID'),
                    'dateFrom' => $request->input('dateFrom'),
                    'dateTo' => $request->input('dateTo')
                ]
            );
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . mb_substr($e->getMessage(), 0, 300)
            ], 500, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }

    /**
     * GRN line items only (dbo.ProcGrnDetDetails).
     * GET /api/gateEntry/grn/{id}?locationID=8
     */
    public function grnDetails($id, Request $request)
    {
        @set_time_limit(120);

        $locationID = (int) $request->input('locationID');

        if (!$locationID) {
            return response()->json([
                'success' => false,
                'message' => 'Location ID is required.',
            ], 422);
        }

        try {
            $items = GRN::getGRNItemData((int) $id, $locationID);

            return response()->json([
                'success' => true,
                'grnId' => (int) $id,
                'locationID' => $locationID,
                'items' => array_values($items),
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('API Gate Entry GRN Details Error', [
                'grnId' => $id,
                'locationID' => $locationID,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . mb_substr($e->getMessage(), 0, 300),
            ], 500, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }

    /**
     * Shared data for the details JSON and the PDF.
     * Throws on SP failure so each endpoint can answer in its own format.
     */
    private function loadDetailsData($id, Request $request): array
    {
        @set_time_limit(120);

        $grnId = (int) $id;
        $locationID = (int) $request->query('locationID');
        $dateFromInput = (string) $request->query('dateFrom', '');
        $dateToInput = (string) $request->query('dateTo', '');

        $location = null;
        try {
            $location = DB::table('Location')->where('LocationID', $locationID)->first();
        } catch (\Throwable $e) {
            $location = null;
        }

        // Gate Entry row from the list cache (no extra SP call)
        $entry = null;
        try {
            $dateFrom = $dateFromInput !== ''
                ? Carbon::parse($dateFromInput)->startOfDay()->format('Y-m-d H:i:s')
                : Carbon::now()->subYears(10)->startOfDay()->format('Y-m-d H:i:s');
            $dateTo = $dateToInput !== ''
                ? Carbon::parse($dateToInput)->endOfDay()->format('Y-m-d H:i:s')
                : Carbon::now()->addYears(5)->endOfDay()->format('Y-m-d H:i:s');

            $entry = GateEntry::findWHGoodsRowCached($locationID, $dateFrom, $dateTo, $grnId);
        } catch (\Throwable $e) {
            $entry = null;
        }

        $items = GRN::getGRNItemData($grnId, $locationID);

        $totalQty = 0;
        $totalAmount = 0;
        foreach ($items as $it) {
            $totalQty += (float) ($it->Qty ?? 0);
            $totalAmount += (float) ($it->Amount ?? 0);
        }

        return [
            'grnId' => $grnId,
            'locationID' => $locationID,
            'location' => $location,
            'entry' => $entry,
            'items' => array_values($items),
            'totalQty' => $totalQty,
            'totalAmount' => round($totalAmount, 2),
            'query' => array_filter([
                'locationID' => $locationID ?: null,
                'dateFrom' => $dateFromInput ?: null,
                'dateTo' => $dateToInput ?: null,
            ]),
        ];
    }

    /**
     * Gate Entry details (entry row + GRN items + totals) for the app.
     * GET /api/gateEntry/details/{id}?locationID=8&dateFrom=2026-08-24&dateTo=2026-09-23
     */
    public function details($id, Request $request)
    {
        if (!(int) $request->query('locationID')) {
            return response()->json([
                'success' => false,
                'message' => 'Location ID is required.',
            ], 422);
        }

        try {
            $data = $this->loadDetailsData($id, $request);

            if (empty($data['items'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No details found for this GRN.',
                    'grnId' => $data['grnId'],
                    'locationID' => $data['locationID'],
                    'items' => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'grnId' => $data['grnId'],
                'locationID' => $data['locationID'],
                'locationName' => $data['location']->LocationName ?? null,
                'entry' => $data['entry'],
                'items' => $data['items'],
                'totalItems' => count($data['items']),
                'totalQty' => $data['totalQty'],
                'totalAmount' => $data['totalAmount'],
                'pdfUrl' => url('api/gateEntry/details/' . $data['grnId'] . '/pdf') . '?' . http_build_query($data['query']),
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('API Gate Entry Details Error', [
                'grnId' => $id,
                'locationID' => $request->query('locationID'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load details for this GRN.',
            ], 500);
        }
    }

    /**
     * Gate Entry details as PDF (same layout as the web PDF).
     * GET /api/gateEntry/details/{id}/pdf?locationID=8&dateFrom=...&dateTo=...
     * Success: application/pdf download. Failure: JSON with success=false.
     */
    public function downloadPdf($id, Request $request)
    {
        @ini_set('memory_limit', '512M');

        if (!(int) $request->query('locationID')) {
            return response()->json([
                'success' => false,
                'message' => 'Location ID is required.',
            ], 422);
        }

        try {
            $data = $this->loadDetailsData($id, $request);
        } catch (\Throwable $e) {
            Log::error('API Gate Entry PDF Data Error', [
                'grnId' => $id,
                'locationID' => $request->query('locationID'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load details for this GRN.',
            ], 500);
        }

        if (empty($data['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'No details found to generate PDF.',
            ], 404);
        }

        try {
            $pdf = Pdf::loadView('gateEntry.pdf', $data)->setPaper('a4', 'landscape');

            return $pdf->download('GateEntry_GRN_' . $data['grnId'] . '_' . date('Y-m-d') . '.pdf');
        } catch (\Throwable $e) {
            Log::error('API Gate Entry PDF Error', [
                'grnId' => $data['grnId'],
                'locationID' => $data['locationID'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PDF could not be generated. Please try again.',
            ], 500);
        }
    }
}
