<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GRN;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GRNController extends Controller
{
    public function list()
    {
        $supportedLocationIds = [8, 26, 29];

        $locations = Cache::remember('grn.locations.supported', 1800, fn () => DB::table('Location')
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

        return view('grn.list', compact('locations'));
    }

    /**
     * GRN Detail page blade view - fetch header and render
     */
    public function detailPage($id, Request $request)
    {
        try {
            $locationID = $request->query('locationID', \App\Services\UserAccessService::defaultLocationId(8));
            $dateParam = $request->query('date', date('Y-m-d'));

            try {
                $refDate = Carbon::parse($dateParam);
            } catch (\Exception $e) {
                $refDate = Carbon::now();
            }

            // Fast path: the list already sends the GRN date. Fetch only that
            // month first instead of loading the complete 15-year history.
            // Safe fallback below preserves the old behaviour for direct/old links.
            $startDate = $refDate->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s');
            $endDate = $refDate->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');

            $results = GRN::getGRNData($startDate, $endDate, intval($locationID), 0);
            $grnHeader = collect($results)->first(function ($item) use ($id) {
                return ($item->GRNID ?? null) == $id;
            });

            // Preserve existing functionality if date is missing/wrong in an old link.
            if (! $grnHeader) {
                $fallbackStart = Carbon::now()->subYears(10)->startOfDay()->format('Y-m-d H:i:s');
                $fallbackEnd = Carbon::now()->addYears(5)->endOfDay()->format('Y-m-d H:i:s');
                $fallbackResults = GRN::getGRNData($fallbackStart, $fallbackEnd, intval($locationID), 0);
                $grnHeader = collect($fallbackResults)->first(function ($item) use ($id) {
                    return ($item->GRNID ?? null) == $id;
                });
            }

            if (! $grnHeader) {
                Log::warning('GRN not found', ['grnId' => $id, 'locationID' => $locationID]);

                // Still load page but show empty
                return view('grn.detail', [
                    'grnId' => $id,
                    'grnHeader' => null,
                    'locationID' => $locationID,
                    'date' => $dateParam,
                ]);
            }

            return view('grn.detail', [
                'grnId' => $id,
                'grnHeader' => $grnHeader,
                'locationID' => $locationID,
                'date' => $dateParam,
            ]);
        } catch (\Exception $e) {
            Log::error('GRN Detail Page Error: '.$e->getMessage());

            return view('grn.detail', [
                'grnId' => $id,
                'grnHeader' => null,
                'locationID' => 8,
                'date' => date('Y-m-d'),
            ]);
        }
    }

    /**
     * GRN datatable - list all GRNs with filters
     * Agar koi date filter nahi hai to saara data location ke liye fetch hoga
     */
    public function datatable(Request $request)
    {
        try {
            $locationID = $request->input('locationID', \App\Services\UserAccessService::defaultLocationId(8));
            $dateFrom = $request->input('dateFrom');
            $dateTo = $request->input('dateTo');
            $start = intval($request->input('start', 0));
            $length = min(max(intval($request->input('length', 50)), 1), 100);
            $searchValue = $request->input('search.value', '');

            // Agar date filter empty hai to unlimited data fetch karo
            if (empty($dateFrom) && empty($dateTo)) {
                // Last 10 years se next 5 years tak (basically unlimited)
                $dateFrom = Carbon::now()->subYears(10)->startOfDay()->format('Y-m-d H:i:s');
                $dateTo = Carbon::now()->addYears(5)->endOfDay()->format('Y-m-d H:i:s');
            } else {
                // Agar koi ek date ho to use it
                if (empty($dateFrom)) {
                    $dateFrom = Carbon::now()->subYears(10)->startOfDay()->format('Y-m-d H:i:s');
                }
                if (empty($dateTo)) {
                    $dateTo = Carbon::now()->addYears(5)->endOfDay()->format('Y-m-d H:i:s');
                }
            }

            // Fast path: SQL Server performs filtering + pagination. This avoids
            // transferring the entire date range to PHP on every search.
            $fastPage = GRN::getFastPaginatedData(
                $dateFrom,
                $dateTo,
                (int) $locationID,
                (string) $searchValue,
                $start,
                $length
            );

            if ($fastPage !== null) {
                $pageRows = array_map(function ($item) use ($locationID) {
                    $item->LocationID = (int) $locationID;
                    return $item;
                }, $fastPage['data']);

                return response()->json([
                    'success' => true,
                    'data' => array_values($pageRows),
                    'recordsTotal' => $fastPage['recordsTotal'],
                    'recordsFiltered' => $fastPage['recordsFiltered'],
                    'totals' => $this->getTotals($locationID, $dateFrom, $dateTo),
                ]);
            }

            // Compatibility fallback: preserve the existing stored-procedure
            // behavior if the live DB schema cannot support the direct path.
            $results = GRN::getGRNData($dateFrom, $dateTo, intval($locationID), 0);

            // Search is performed on the already cached procedure result, but the
            // filtered ID/result set is also cached. Pagination / repeated requests
            // for the same term therefore do not rescan the complete history.
            if (! empty($searchValue)) {
                $searchStr = mb_strtolower(trim($searchValue), 'UTF-8');
                $searchCacheKey = 'grn.search.' . md5(json_encode([
                    $dateFrom, $dateTo, (int) $locationID, $searchStr
                ]));

                $results = Cache::remember($searchCacheKey, 180, function () use ($results, $searchStr) {
                    return array_values(array_filter($results, static function ($item) use ($searchStr) {
                        $haystack = mb_strtolower(
                            (string) ($item->GmNo ?? '') . ' ' .
                            (string) ($item->Supplier ?? '') . ' ' .
                            (string) ($item->FloorName ?? ''),
                            'UTF-8'
                        );

                        return str_contains($haystack, $searchStr);
                    }));
                });
            }

            // Pagination
            $total = count($results);
            $paginatedResults = array_slice($results, $start, $length);

            // Add locationID to each result for proper linking
            $paginatedResults = array_map(function ($item) use ($locationID) {
                $item->LocationID = $locationID;

                return $item;
            }, $paginatedResults);

            return response()->json([
                'success' => true,
                'data' => array_values($paginatedResults),
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'totals' => $this->getTotals($locationID, $dateFrom, $dateTo),
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'GRN Datatable Error',
                [
                    'message' => $e->getMessage(),
                    'locationID' => $request->input('locationID'),
                    'dateFrom' => $request->input('dateFrom'),
                    'dateTo' => $request->input('dateTo'),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get GRN details - just fetch items
     * Header data is already available in detail page from list
     */
    // public function show($id, Request $request)
    // {
    //     try {
    //         // Only fetch items - header is already in detail page
    //         $items = GRN::getGRNItemData($id);

    //         return response()->json([
    //             'success'=>true,
    //             'items'=> $items
    //         ]);
    //     }
    //     catch(\Throwable $e){
    //         Log::error('GRN Show Error: '.$e->getMessage());
    //         return response()->json([
    //             'success'=>false,
    //             'message'=>'Error loading items: '.$e->getMessage()
    //         ],500);
    //     }
    // }
   public function show($id, Request $request)
{
    ini_set('max_execution_time', 180);
    set_time_limit(180);

    try {

        $locationID = (int) $request->input('locationID');

        if (!$locationID) {
            return response()->json([
                'success' => false,
                'message' => 'Location ID is required.',
            ], 422);
        }

        $items = GRN::getGRNItemData(
            $id,
            $locationID
        );

        return response()->json([
            'success'    => true,
            'grnId'      => (int) $id,
            'locationID' => $locationID,
            'items'      => $items,
            'totals'     => [
                'total_entries' => count($items),
                'total_qty' => collect($items)->sum(fn($x) => (float)($x->Qty ?? $x->quantity ?? 0)),
                'total_amount' => collect($items)->sum(fn($x) => (float)($x->Amount ?? 0)),
            ],
        ]);

    } catch (\Throwable $e) {

        Log::error('GRN Show Error', [
            'grnId'      => $id,
            'locationID' => $locationID ?? null,
            'error'      => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Download GRN as PDF
     */
   public function downloadPdf($id, Request $request)
{
    // PHP timeout temporarily increase
    ini_set('max_execution_time', 180);
    set_time_limit(180);

    try {

        $locationID = (int) $request->query('locationID');
        $dateParam  = $request->query('date');

        if (!$locationID) {
            return redirect()
                ->back()
                ->with('error', 'Location ID is required.');
        }

        if (!$dateParam) {
            return redirect()
                ->back()
                ->with('error', 'GRN date is required.');
        }

        /*
        |--------------------------------------------------------------------------
        | Use GRN month instead of fetching 15 years data
        |--------------------------------------------------------------------------
        */

        try {

            $refDate = Carbon::parse($dateParam);

        } catch (\Throwable $e) {

            $refDate = Carbon::now();
        }

        $startDate = $refDate
            ->copy()
            ->startOfMonth()
            ->startOfDay()
            ->format('Y-m-d H:i:s');

        $endDate = $refDate
            ->copy()
            ->endOfMonth()
            ->endOfDay()
            ->format('Y-m-d H:i:s');


        Log::info('GRN PDF Fetching Header', [
            'grnId'      => $id,
            'locationID' => $locationID,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $results = GRN::getGRNData(
            $startDate,
            $endDate,
            $locationID,
            0
        );


        $grnHeader = collect($results)->first(
            function ($item) use ($id) {

                return (int) ($item->GRNID ?? 0)
                    === (int) $id;

            }
        );


        if (!$grnHeader) {

            Log::warning('GRN PDF Header Not Found', [
                'grnId'      => $id,
                'locationID' => $locationID,
                'date'       => $dateParam,
            ]);

            return redirect()
                ->back()
                ->with('error', 'GRN not found.');
        }


        /*
        |--------------------------------------------------------------------------
        | Items
        |--------------------------------------------------------------------------
        */

        $items = GRN::getGRNItemData(
            $id,
            $locationID
        );


        Log::info('GRN PDF Items Loaded', [
            'grnId'      => $id,
            'locationID' => $locationID,
            'itemCount'  => count($items),
        ]);


        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView('grn.pdf', [
            'grn'        => $grnHeader,
            'items'      => $items,
            'locationID' => $locationID,
        ])
        ->setPaper('a4', 'landscape');


        $fileName =
            'GRN_' .
            ($grnHeader->GmNo ?? $id) .
            '_' .
            date('Y-m-d') .
            '.pdf';


        return $pdf->download($fileName);


    } catch (\Throwable $e) {

        Log::error('GRN Download PDF Error', [
            'grnId'      => $id,
            'locationID' => $request->query('locationID'),
            'message'    => $e->getMessage(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
        ]);


        return redirect()
            ->back()
            ->with(
                'error',
                'PDF Error: ' . $e->getMessage()
            );
    }
}

    /**
     * Calculate GRN totals for selected location.
     */
    private function getTotals($locationID, $dateFrom = null, $dateTo = null)
    {
        try {
            if (!$dateFrom) {
                $dateFrom = Carbon::now()->subYears(10)->startOfDay()->format('Y-m-d H:i:s');
            }
            if (!$dateTo) {
                $dateTo = Carbon::now()->addYears(5)->endOfDay()->format('Y-m-d H:i:s');
            }

            $rows = GRN::getGRNData($dateFrom, $dateTo, (int)$locationID, 0);

            return [
                'total_entries' => count($rows),
                'total_billing' => collect($rows)->sum(fn($x) => (float)($x->Billing ?? 0)),
                'total_grn_amount' => collect($rows)->sum(fn($x) => (float)($x->GRNAmount ?? 0)),
                'total_bill_amount' => collect($rows)->sum(fn($x) => (float)($x->BillAmount ?? 0)),
                'total_mrp' => collect($rows)->sum(fn($x) => (float)($x->TotalMRP ?? 0)),
                'total_grn_no' => collect($rows)->sum(fn($x) => (float)($x->GRNNo ?? 0)),
            ];
        } catch (\Throwable $e) {
            Log::error('GRN Total Error: '.$e->getMessage());
            return [
                'total_entries'=>0,
                'total_billing'=>0,
                'total_grn_amount'=>0,
                'total_bill_amount'=>0,
                'total_mrp'=>0,
                'total_grn_no'=>0,
            ];
        }
    }

}
