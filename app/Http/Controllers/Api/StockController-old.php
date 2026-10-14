<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HSStock;
use App\Services\FieldPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class StockController extends Controller
{
    // ============ RESOLVE LOCATION COLUMN ============
    private function resolveLocationColumn($code)
    {
        static $columns = null;
        if ($columns === null) {
            $columns = Cache::remember('schema.hsstock.columns', 3600, fn () => Schema::getColumnListing('HSStock'));
        }
        foreach ($columns as $col) {
            if (strcasecmp($col, $code) === 0) {
                return $col;
            }
        }
        return null;
    }

    // ============ GET DYNAMIC LOCATION CODES WITH FIELD PERMISSIONS ============
    private function getDynamicLocationCodes()
    {
        try {
            $allCodes = Cache::remember('stock.location-codes.all', 1800, fn () => DB::table('Location')
                ->whereNotNull('Locationcode')
                ->where('Locationcode', '<>', '')
                ->orderBy('Locationcode')
                ->pluck('Locationcode')
                ->toArray());

            $user = auth()->user();
            if (FieldPermissionService::isAdmin($user)) {
                return $allCodes;
            }

            $allowed = array_map('strtolower', FieldPermissionService::allowedFields($user, 'HSStock'));

            return array_values(array_filter($allCodes, function ($code) use ($allowed) {
                return in_array(strtolower((string) $code), $allowed, true);
            }));
        } catch (\Throwable $e) {
            Log::warning('Could not fetch permission-aware location codes', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function list()
    {
        try {
            $sections = Cache::remember('stock.filters.sections', 1800, fn () => DB::table('HSStock')->whereNotNull('SectionName')->where('SectionName', '<>', '')->distinct()->orderBy('SectionName')->pluck('SectionName')->toArray());
            $companies = Cache::remember('stock.filters.companies', 1800, fn () => DB::table('HSStock')->whereNotNull('CompanyName')->where('CompanyName', '<>', '')->distinct()->orderBy('CompanyName')->pluck('CompanyName')->toArray());
            $products = Cache::remember('stock.filters.products', 1800, fn () => DB::table('HSStock')->whereNotNull('ProductName')->where('ProductName', '<>', '')->distinct()->orderBy('ProductName')->pluck('ProductName')->toArray());
            $units = Cache::remember('stock.filters.units', 1800, fn () => DB::table('HSStock')->whereNotNull('Unit')->where('Unit', '<>', '')->distinct()->orderBy('Unit')->pluck('Unit')->toArray());
            $quantities = Cache::remember('stock.filters.quantities', 1800, fn () => DB::table('HSStock')->whereNotNull('StkQty')->distinct()->orderBy('StkQty')->pluck('StkQty')->toArray());
            $colors = Cache::remember('stock.filters.colors', 1800, fn () => DB::table('HSStock')->whereNotNull('Color')->where('Color', '<>', '')->distinct()->orderBy('Color')->pluck('Color')->toArray());
            $sizes = Cache::remember('stock.filters.sizes', 1800, fn () => DB::table('HSStock')->whereNotNull('Size')->where('Size', '<>', '')->distinct()->orderBy('Size')->pluck('Size')->toArray());
            $qualities = Cache::remember('stock.filters.qualities', 1800, fn () => DB::table('ItemDet')->whereNotNull('QualityName')->where('QualityName', '<>', '')->distinct()->orderBy('QualityName')->pluck('QualityName')->toArray());
            $suppliers = Cache::remember('stock.filters.suppliers', 1800, fn () => DB::table('ItemDet')->whereNotNull('SupplierName')->where('SupplierName', '<>', '')->distinct()->orderBy('SupplierName')->pluck('SupplierName')->toArray());
            $serialNos = Cache::remember('stock.filters.serials', 1800, fn () => DB::table('ItemDet')->whereNotNull('SerialNo')->where('SerialNo', '<>', '')->distinct()->orderBy('SerialNo')->pluck('SerialNo')->toArray());

            // Dynamic location codes लाओ
            $locationCodes = $this->getDynamicLocationCodes();

            // Locations का full data
            $locations = Cache::remember('stock.locations.full', 1800, fn () => DB::table('Location')
                ->whereNotNull('Locationcode')->where('Locationcode', '<>', '')
                ->orderBy('LocationName')
                ->get(['LocationID', 'Locationcode', 'LocationName','Color']));
            $allowedLocationCodes = array_map('strtolower', $locationCodes);
            $locations = $locations
                ->filter(function ($loc) use ($allowedLocationCodes) {
                    if (FieldPermissionService::isAdmin(auth()->user())) {
                        return true;
                    }
                    return in_array(strtolower((string) $loc->Locationcode), $allowedLocationCodes, true);
                })
                ->map(function ($loc) {
                    $loc->linked = $this->resolveLocationColumn($loc->Locationcode) !== null;
                    return $loc;
                })->values();

            return view('stock.list', [
                'sections'   => array_values(array_filter($sections)),
                'companies'  => array_values(array_filter($companies)),
                'products'   => array_values(array_filter($products)),
                'units'      => array_values(array_filter($units)),
                'quantities' => array_values(array_filter($quantities)),
                'colors'     => array_values(array_filter($colors)),
                'sizes'      => array_values(array_filter($sizes)),
                'qualities'  => array_values(array_filter($qualities)),
                'suppliers'  => array_values(array_filter($suppliers)),
                'serialNos'  => array_values(array_filter($serialNos)),
                'locations'  => $locations,
                'locationCodes' => $locationCodes  // ✅ Dynamic location codes
            ]);
        } catch (\Exception $e) {
            Log::error('Stock List Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading stock list');
        }
    }

    // ============ ALL FILTER OPTIONS (JSON — mobile app) ============
    // Same independent, un-scoped lists the web list() view renders into its
    // <select> filters — just returned as JSON instead of Blade variables.
    public function allOptions()
    {
        try {
            $sections   = Cache::remember('stock.filters.sections', 1800, fn () => DB::table('HSStock')->whereNotNull('SectionName')->where('SectionName', '<>', '')->distinct()->orderBy('SectionName')->pluck('SectionName')->toArray());
            $companies  = Cache::remember('stock.filters.companies', 1800, fn () => DB::table('HSStock')->whereNotNull('CompanyName')->where('CompanyName', '<>', '')->distinct()->orderBy('CompanyName')->pluck('CompanyName')->toArray());
            $products   = Cache::remember('stock.filters.products', 1800, fn () => DB::table('HSStock')->whereNotNull('ProductName')->where('ProductName', '<>', '')->distinct()->orderBy('ProductName')->pluck('ProductName')->toArray());
            $units      = Cache::remember('stock.filters.units', 1800, fn () => DB::table('HSStock')->whereNotNull('Unit')->where('Unit', '<>', '')->distinct()->orderBy('Unit')->pluck('Unit')->toArray());
            $quantities = Cache::remember('stock.filters.quantities', 1800, fn () => DB::table('HSStock')->whereNotNull('StkQty')->distinct()->orderBy('StkQty')->pluck('StkQty')->toArray());
            $colors     = Cache::remember('stock.filters.colors', 1800, fn () => DB::table('HSStock')->whereNotNull('Color')->where('Color', '<>', '')->distinct()->orderBy('Color')->pluck('Color')->toArray());
            $sizes      = Cache::remember('stock.filters.sizes', 1800, fn () => DB::table('HSStock')->whereNotNull('Size')->where('Size', '<>', '')->distinct()->orderBy('Size')->pluck('Size')->toArray());
            $qualities  = Cache::remember('stock.filters.qualities', 1800, fn () => DB::table('ItemDet')->whereNotNull('QualityName')->where('QualityName', '<>', '')->distinct()->orderBy('QualityName')->pluck('QualityName')->toArray());
            $suppliers  = Cache::remember('stock.filters.suppliers', 1800, fn () => DB::table('ItemDet')->whereNotNull('SupplierName')->where('SupplierName', '<>', '')->distinct()->orderBy('SupplierName')->pluck('SupplierName')->toArray());
            $serialNos  = Cache::remember('stock.filters.serials', 1800, fn () => DB::table('ItemDet')->whereNotNull('SerialNo')->where('SerialNo', '<>', '')->distinct()->orderBy('SerialNo')->pluck('SerialNo')->toArray());

            return response()->json([
                'success' => true,
                'data' => [
                    'sections'   => array_values(array_filter($sections)),
                    'companies'  => array_values(array_filter($companies)),
                    'products'   => array_values(array_filter($products)),
                    'units'      => array_values(array_filter($units)),
                    'quantities' => array_values(array_filter($quantities)),
                    'colors'     => array_values(array_filter($colors)),
                    'sizes'      => array_values(array_filter($sizes)),
                    'qualities'  => array_values(array_filter($qualities)),
                    'suppliers'  => array_values(array_filter($suppliers)),
                    'serial_nos' => array_values(array_filter($serialNos)),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Stock All-Options Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not load filter options.'], 500);
        }
    }
    // ============ DYNAMIC STOCK QUERY ============
    private function stockQuery()
    {
        // Dynamic location codes लाओ
        $locationCodes = $this->getDynamicLocationCodes();

        // Base select columns (without hardcoded location columns)
        $selectColumns = [
            'H.ItemDetID','H.ItemCode','H.CompanyName','H.ItemName','H.ProductName','H.DesignName','H.Color','H.StkQty','H.WHQTY','H.MRP','H.Rate','H.SectionName','H.HsStockID',
            'H.Unit','H.Size','H.ACost','H.AccountID','H.COLLECTIONNAME','H.HSNCODE',
            'H.GST','H.DPEX','H.MU','H.RJCACCOUNTID',
            'I.QualityName','I.SerialNo','I.SupplierName','I.SupplierMobileNo','I.ContactPerson','I.ItemImage','I.IImage',
        ];

        // Dynamic location columns को add करो
        foreach ($locationCodes as $code) {
            $resolvedCol = $this->resolveLocationColumn($code);
            if ($resolvedCol) {
                $selectColumns[] = "H.$resolvedCol";
            }
        }

        return DB::table('HSStock as H')
            ->leftJoin('ItemDet as I','H.ItemDetID','=','I.SysItemDetID')
            ->select($selectColumns);
    }

    private function applyFilters($query, Request $request)
    {
        // Location is fully independent of Section/Company
        if ($request->filled('location')) {
            $col = $this->resolveLocationColumn($request->input('location'));
            if ($col) {
                $query->where("H.$col", '>', 0);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        if ($request->filled('section')) {
            $query->where('H.SectionName',$request->input('section'));
        }
        if ($request->filled('company')) {
            $query->where('H.CompanyName',$request->input('company'));
        }
        if ($request->filled('product')) {
            $query->where('H.ProductName',$request->input('product'));
        }
        if ($request->filled('item_name')) {
            $query->where('H.ItemName',$request->input('item_name'));
        }
        if ($request->filled('design')) {
            $query->where('H.DesignName',$request->input('design'));
        }
        if ($request->filled('color')) {
            $query->where('H.Color',$request->input('color'));
        }
        if ($request->filled('size')) {
            $query->where('H.Size',$request->input('size'));
        }
        if ($request->filled('unit')) {
            $query->where('H.Unit',$request->input('unit'));
        }
        if ($request->filled('quantity')) {
            $query->where('H.StkQty',$request->input('quantity'));
        }
        if ($request->filled('supplier')) {
            $query->where('I.SupplierName',$request->input('supplier'));
        }
        if ($request->filled('serial_no')) {
            $query->where('I.SerialNo',$request->input('serial_no'));
        }
        if ($request->filled('quality')) {
            $query->where('I.QualityName',$request->input('quality'));
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('H.ItemCode','like',"%{$search}%")
                        ->orWhere('H.ItemName','like',"%{$search}%")
                        ->orWhere('H.DesignName','like',"%{$search}%")
                        ->orWhere('H.Color','like',"%{$search}%")
                        ->orWhere('I.SupplierName','like',"%{$search}%")
                        ->orWhere('I.SerialNo','like',"%{$search}%");
                });
            }
        }
        return $query;
    }

    public function itemLocations(Request $request)
    {
        try {
            $request->validate([
                'itemCode' => 'required|string',
                'supplier' => 'required|string'
            ]);

            $itemCode = $request->input('itemCode');
            $supplier = $request->input('supplier');
            $locationCodes = $this->getDynamicLocationCodes();

            // Base select columns
            $selectColumns = [
                'H.ItemCode',
                'H.ItemName',
                'H.StkQty',
                'H.Rate',
                'H.SectionName',
            ];

            // Dynamic location columns को add करो
            foreach ($locationCodes as $code) {
                $resolvedCol = $this->resolveLocationColumn($code);
                if ($resolvedCol) {
                    $selectColumns[] = "H.$resolvedCol";
                }
            }

            // Get the specific item with all warehouse locations
            $item = DB::table('HSStock as H')
                ->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID')
                ->select($selectColumns)
                ->where('H.ItemCode', $itemCode)
                ->where('I.SupplierName', $supplier)
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found for this supplier.'
                ], 404);
            }

            // Active location codes find करो
            $activeLocationCodes = [];
            foreach ($locationCodes as $code) {
                $resolvedCol = $this->resolveLocationColumn($code);
                if ($resolvedCol && isset($item->$resolvedCol) && $item->$resolvedCol > 0) {
                    $activeLocationCodes[] = $code;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $item,
                'locationCodes' => $locationCodes,
                'activeLocationCodes' => $activeLocationCodes
            ]);
        } catch (\Exception $e) {
            Log::error('Item Locations Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function supplierLocations(Request $request)
    {
        try {
            $request->validate(['supplier' => 'required|string']);

            $supplier = $request->input('supplier');
            $locationCodes = $this->getDynamicLocationCodes();

            // Base select columns
            $selectColumns = [
                'H.ItemCode',
                'H.ItemName',
                'H.StkQty',
                'H.Rate',
                'H.SectionName',
            ];

            // Dynamic location columns को add करो
            foreach ($locationCodes as $code) {
                $resolvedCol = $this->resolveLocationColumn($code);
                if ($resolvedCol) {
                    $selectColumns[] = "H.$resolvedCol";
                }
            }

            // Get all items from this supplier with location info
            $items = DB::table('HSStock as H')
                ->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID')
                ->select($selectColumns)
                ->where('I.SupplierName', $supplier)
                ->orderBy('H.ItemCode')
                ->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No stock items found for this supplier.'
                ], 404);
            }

            // Transform: add a "Location" column that shows which warehouse has stock
            $items = $items->map(function ($item) use ($locationCodes) {
                $locations = [];

                // Check all warehouse columns dynamically
                foreach ($locationCodes as $code) {
                    $resolvedCol = $this->resolveLocationColumn($code);
                    if ($resolvedCol && isset($item->$resolvedCol) && $item->$resolvedCol > 0) {
                        $locations[] = "$code: {$item->$resolvedCol}";
                    }
                }

                $item->Location = !empty($locations) ? implode(' | ', $locations) : 'No Stock';

                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $items,
                'locationCodes' => $locationCodes
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier Locations Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function imageProxy($filename)
    {
        try {
            if (!preg_match('/^[\w\-\.]+$/', $filename)) {
                return response()->json(['success' => false, 'message' => 'Invalid filename'], 400);
            }

            $itemCode = pathinfo($filename, PATHINFO_FILENAME);
            $localPath = $this->findLocalItemImagePath($itemCode);
            if ($localPath) {
                $mimeType = function_exists('mime_content_type') ? mime_content_type($localPath) : 'image/jpeg';
                return response(file_get_contents($localPath), 200)
                    ->header('Content-Type', $mimeType ?: 'image/jpeg')
                    ->header('Cache-Control', 'public, max-age=86400');
            }

            $remoteUrl = "http://web.homesaaz.in:85/{$filename}";
            $response = Http::timeout(10)->get($remoteUrl);

            if ($response->failed()) {
                return response()->json(['success' => false, 'message' => 'Image not found on server', 'status' => $response->status()], $response->status());
            }

            return response($response->body(), 200)
                ->header('Content-Type', $response->header('Content-Type') ?? 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=86400');
        } catch (\Exception $e) {
            Log::error('Image Proxy Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching image'], 500);
        }
    }

    private function findLocalItemImagePath($itemCode)
    {
        $folder = public_path('items/' . $itemCode);
        if (!is_dir($folder)) {
            return null;
        }
        $files = array_filter(glob($folder . '/*'), 'is_file');
        return $files ? reset($files) : null;
    }

    private function normalizeImageFilename($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        $filename = basename(str_replace('\\', '/', $path));

        return $filename !== '' ? $filename : null;
    }

    private function apiImageProxyUrl(?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }

        // API clients can use this URL directly.
        return url('/stock/image-proxy/' . rawurlencode($filename));
    }

    private function getItemImageName($itemCode)
    {
        return Cache::remember('stock.image-name.' . md5((string) $itemCode), 3600, function () use ($itemCode) {
            try {
                $result = DB::select("EXEC dbo.ProcItemCodeDet @ItemCode = ?", [$itemCode]);
                return !empty($result) ? ($result[0]->ImageName ?? null) : null;
            } catch (\Exception $e) {
                Log::error('Image fetch error: ' . $e->getMessage());
                return null;
            }
        });
    }

    public function getDataTable(Request $request)
    {
        set_time_limit(180);
        try {
            $allFilters = ['location','section','company','product','item_name','design','color','size','supplier','serial_no','unit','quality','quantity'];
            $hasFilter = false;
            foreach ($allFilters as $filter) {
                if ($request->filled($filter)) {
                    $hasFilter = true;
                    break;
                }
            }

            if (!$hasFilter && !$request->filled('search')) {
                return response()->json(['success' => false, 'message' => 'Please select at least one filter (Location, Section, Company, or others) to search.'], 422);
            }

            $locationCodes = $this->getDynamicLocationCodes();
            $perPage = (int) $request->input('per_page',50);
            if ($perPage <= 0) $perPage = 50;
            $perPage = min($perPage, 100);
            $page = (int) $request->input('page',1);
            if ($page <= 0) $page = 1;

            // Base select columns
            $selectColumns = [
                'H.ItemDetID','H.ItemCode','H.CompanyName','H.ItemName','H.ProductName','H.DesignName','H.Color','H.StkQty','H.WHQTY','H.MRP','H.Rate','H.SectionName','H.HsStockID','H.Unit','H.Size','H.ACost','H.AccountID','H.COLLECTIONNAME','H.HSNCODE','H.GST','H.DPEX','H.MU','H.RJCACCOUNTID',
                'I.QualityName','I.SerialNo','I.SupplierName','I.SupplierMobileNo','I.ContactPerson','I.ItemImage','I.IImage'
            ];

            // Dynamic location columns को add करो
            foreach ($locationCodes as $code) {
                $resolvedCol = $this->resolveLocationColumn($code);
                if ($resolvedCol) {
                    $selectColumns[] = "H.$resolvedCol";
                }
            }

            $rowNumberQuery = DB::table('HSStock as H')->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID')
                ->select(array_merge($selectColumns, [
                    DB::raw('ROW_NUMBER() OVER (ORDER BY H.ItemDetID DESC) as RowNum'),
                ]));
            $rowNumberQuery = $this->applyFilters($rowNumberQuery, $request);

            $start = (($page - 1) * $perPage) + 1;
            $end = $page * $perPage;

            $stocks = DB::table(DB::raw('(' . $rowNumberQuery->toSql() . ') as PagedResult'))
                ->mergeBindings($rowNumberQuery)
                ->whereBetween('RowNum', [$start, $end])
                ->orderBy('RowNum')
                ->get();

            // The filtered stats used to be calculated with window functions on
            // every page request plus a second DISTINCT color query. Cache one
            // aggregate result per filter/search combination so page 2/3/etc only
            // execute the paged row query.
            $statsKeyParts = [];
            foreach (['location','section','company','product','search','item_name','design','color','size','supplier','serial_no','unit','quality','quantity'] as $key) {
                $statsKeyParts[$key] = (string) $request->input($key, '');
            }
            $statsCacheKey = 'stock.stats.' . md5(json_encode($statsKeyParts));
            $stats = Cache::remember($statsCacheKey, 120, function () use ($request) {
                $statsQuery = DB::table('HSStock as H')
                    ->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID');
                $statsQuery = $this->applyFilters($statsQuery, $request);

                return $statsQuery->selectRaw(
                    'COUNT(*) as TotalCount, ' .
                    'SUM(CASE WHEN H.StkQty <= 10 THEN 1 ELSE 0 END) as LowStockCount, ' .
                    'SUM(H.StkQty * H.Rate) as TotalValueSum, ' .
                    'SUM(H.StkQty) as TotalQuantity, ' .
                    'SUM(H.WHQTY) as TotalWHQTY, ' .
                    'SUM(H.MRP) as TotalMRP, ' .
                    'SUM(H.Rate) as TotalRate, ' .
                    'SUM(H.ACost) as TotalCost, ' .
                    "COUNT(DISTINCT CASE WHEN H.Color IS NOT NULL AND H.Color <> '' THEN H.Color END) as UniqueColors"
                )->first();
            });

            $totalCount = (int) ($stats->TotalCount ?? 0);
            $lowStockCount = (int) ($stats->LowStockCount ?? 0);
            $totalValue = (float) ($stats->TotalValueSum ?? 0);
            $uniqueColors = (int) ($stats->UniqueColors ?? 0);

            /*
             * Allocation Stock:
             * - If a Location filter is selected, use only that warehouse/location
             *   column for the filtered stock.
             * - If no Location filter is selected, add all permission-aware
             *   warehouse/location columns.
             *
             * This keeps Allocation Stock location/section/company aware because
             * the same applyFilters() query is used for every calculation.
             */
            $allocationStock = 0.0;
            $selectedLocation = $request->input('location');

            if ($selectedLocation) {
                $resolvedSelectedLocation = $this->resolveLocationColumn($selectedLocation);

                if ($resolvedSelectedLocation) {
                    $allocationQuery = DB::table('HSStock as H')
                        ->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID');

                    $allocationQuery = $this->applyFilters($allocationQuery, $request);

                    $allocationStock = (float) ($allocationQuery->sum("H.$resolvedSelectedLocation") ?? 0);
                }
            } else {
                foreach ($locationCodes as $code) {
                    $resolvedCol = $this->resolveLocationColumn($code);

                    if ($resolvedCol) {
                        $allocationQuery = DB::table('HSStock as H')
                            ->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID');

                        $allocationQuery = $this->applyFilters($allocationQuery, $request);

                        $allocationStock += (float) ($allocationQuery->sum("H.$resolvedCol") ?? 0);
                    }
                }
            }

            // Dynamic location totals
            $locationTotals = [];
            foreach ($locationCodes as $code) {
                $resolvedCol = $this->resolveLocationColumn($code);
                if ($resolvedCol) {
                    $locationQuery = DB::table('HSStock as H')
                        ->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID');
                    $locationQuery = $this->applyFilters($locationQuery, $request);

                    $locationTotals[$code] = (float) ($locationQuery->sum("H.$resolvedCol") ?? 0);
                }
            }

            $stocks->transform(function ($stock) {
                unset($stock->RowNum);

                // WHQTY comes directly from HSStock.WHQTY.
                // Keep the exact API field name expected by the Blade/mobile client.
                $stock->WHQTY = $stock->WHQTY ?? 0;

                return $stock;
            });

            // ItemDet already carries image fields for many rows. Avoid a stored
            // procedure call per row unless both image fields are genuinely empty.
            $imageNameCache = [];
            $stocks->transform(function ($stock) use (&$imageNameCache) {
                $itemCode = trim((string) ($stock->ItemCode ?? ''));

                // Same image source as ItemStock/Web Stock:
                // ProcItemCodeDet ImageName first, then ItemImage/IImage fallback.
                if ($itemCode !== '' && !array_key_exists($itemCode, $imageNameCache)) {
                    $imageNameCache[$itemCode] = $this->getItemImageName($itemCode);
                }

                $imageName = $this->normalizeImageFilename(
                    $imageNameCache[$itemCode] ?? null
                );

                if (!$imageName) {
                    $imageName = $this->normalizeImageFilename(
                        $stock->ItemImage ?? $stock->IImage ?? null
                    );
                }

                $stock->ImageName = $imageName;
                $stock->ImageUrl = $this->apiImageProxyUrl($imageName);

                return $stock;
            });

            return response()->json([
                'success' => true,
                'data' => $stocks,
                'total' => $totalCount,
                'current_page' => $page,
                'last_page' => $totalCount > 0 ? (int) ceil($totalCount / $perPage): 1,
                'per_page' => $perPage,
                'locationCodes' => $locationCodes,  // ✅ Dynamic location codes
                'stats' => [
                    'total_items' => $totalCount,
                    'low_stock_items' => $lowStockCount,
                    'total_value' => round($totalValue,2),
                    'unique_colors' => $uniqueColors,
                    'total_quantity' => (float) ($stats->TotalQuantity ?? 0),
                     'total_whqty' => (float) ($stats->TotalWHQTY ?? 0),
                    'total_mrp' => (float) ($stats->TotalMRP ?? 0),
                    'total_rate' => (float) ($stats->TotalRate ?? 0),
                    'total_cost' => (float) ($stats->TotalCost ?? 0),
                    'allocation_stock' => round($allocationStock, 2),
                    'total_allocation_stock' => round($allocationStock, 2),
                    'location_totals' => $locationTotals
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('DataTable Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSectionsByLocation(Request $request)
    {
        try {
            $request->validate(['location' => 'required|string']);
            $col = $this->resolveLocationColumn($request->input('location'));
            if (!$col) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $sections = DB::table('HSStock')
                ->where($col, '>', 0)
                ->whereNotNull('SectionName')->where('SectionName', '<>', '')
                ->distinct()->orderBy('SectionName')->pluck('SectionName');
            return response()->json(['success' => true, 'data' => $sections]);
        } catch (\Exception $e) {
            Log::error('Sections By Location Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getCompaniesBySection(Request $request)
    {
        try {
            $request->validate(['section' => 'required|string']);
            $query = DB::table('HSStock')->where('SectionName', $request->section);

            if ($request->filled('location')) {
                $col = $this->resolveLocationColumn($request->input('location'));
                if ($col) {
                    $query->where($col, '>', 0);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $companies = $query->whereNotNull('CompanyName')->where('CompanyName', '<>', '')->distinct()->orderBy('CompanyName')->pluck('CompanyName');
            return response()->json(['success' => true, 'data' => $companies]);
        } catch (\Exception $e) {
            Log::error('Companies By Section Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getFilterOptions(Request $request)
    {
        if (!$request->filled('section')) {
            return response()->json(['success' => false, 'message' => 'Section is required.'], 422);
        }

        $data = ['item_names' => [], 'designs' => [], 'colors' => [], 'sizes' => [], 'units' => [], 'suppliers' => [], 'serial_nos' => [], 'qualities' => []];
        $errors = [];

        try {
            $hsQuery = DB::table('HSStock as H')->where('H.SectionName', $request->section);
            if ($request->filled('company')) {
                $hsQuery->where('H.CompanyName', $request->company);
            }
            $data['item_names'] = (clone $hsQuery)->whereNotNull('H.ItemName')->where('H.ItemName', '<>', '')->distinct()->orderBy('H.ItemName')->pluck('H.ItemName');
            $data['designs'] = (clone $hsQuery)->whereNotNull('H.DesignName')->where('H.DesignName', '<>', '')->distinct()->orderBy('H.DesignName')->pluck('H.DesignName');
            $data['colors'] = (clone $hsQuery)->whereNotNull('H.Color')->where('H.Color', '<>', '')->distinct()->orderBy('H.Color')->pluck('H.Color');
            $data['sizes'] = (clone $hsQuery)->whereNotNull('H.Size')->where('H.Size', '<>', '')->distinct()->orderBy('H.Size')->pluck('H.Size');
            $data['units'] = (clone $hsQuery)->whereNotNull('H.Unit')->where('H.Unit', '<>', '')->distinct()->orderBy('H.Unit')->pluck('H.Unit');
        } catch (\Exception $e) {
            Log::error('Filter Options (HSStock) Error: ' . $e->getMessage());
            $errors['hsstock'] = $e->getMessage();
        }

        try {
            $itemDetIdQuery = DB::table('HSStock as H')->where('H.SectionName', $request->section)->whereNotNull('H.ItemDetID');
            if ($request->filled('company')) {
                $itemDetIdQuery->where('H.CompanyName', $request->company);
            }
            $itemDetIds = $itemDetIdQuery->pluck('H.ItemDetID');
            $idQuery = DB::table('ItemDet as I')->whereIn('I.SysItemDetID', $itemDetIds);
            $data['suppliers'] = (clone $idQuery)->whereNotNull('I.SupplierName')->where('I.SupplierName', '<>', '')->distinct()->orderBy('I.SupplierName')->pluck('I.SupplierName');
            $data['serial_nos'] = (clone $idQuery)->whereNotNull('I.SerialNo')->where('I.SerialNo', '<>', '')->distinct()->orderBy('I.SerialNo')->pluck('I.SerialNo');
            $data['qualities'] = (clone $idQuery)->whereNotNull('I.QualityName')->where('I.QualityName', '<>', '')->distinct()->orderBy('I.QualityName')->pluck('I.QualityName');
        } catch (\Exception $e) {
            Log::error('Filter Options (ItemDet) Error: ' . $e->getMessage());
            $errors['itemdet'] = $e->getMessage();
        }

        return response()->json(['success' => empty($errors) || !empty(array_filter($data)), 'data' => $data, 'errors' => $errors]);
    }
 
    public function show($id)
    {
        try {
            // Dynamic location codes लाओ
            $locationCodes = $this->getDynamicLocationCodes();
            
            // Base select columns
            $selectColumns = [
                'H.ItemDetID','H.ItemCode','H.CompanyName','H.ItemName','H.ProductName','H.DesignName','H.Color','H.StkQty','H.WHQTY','H.MRP','H.Rate','H.SectionName','H.HsStockID','H.Unit','H.Size',
                'H.ACost','H.AccountID','H.COLLECTIONNAME','H.HSNCODE','H.GST','H.DPEX','H.MU','H.RJCACCOUNTID',
                'I.SysItemDetID','I.QualityName','I.SerialNo','I.SupplierName','I.SupplierMobileNo','I.ContactPerson','I.ItemImage','I.IImage'
            ];
            
            // Dynamic location columns को add करो
            foreach ($locationCodes as $code) {
                $resolvedCol = $this->resolveLocationColumn($code);
                if ($resolvedCol) {
                    $selectColumns[] = "H.$resolvedCol";
                }
            }
            
            $stock = DB::table('HSStock as H')
                ->leftJoin('ItemDet as I','H.ItemDetID','=','I.SysItemDetID')
                ->select($selectColumns)
                ->where('H.ItemDetID', $id)
                ->first();
            
            if (!$stock) {
                return response()->json(['success' => false, 'message' => 'Stock not found', 'id' => $id], 404);
            }
            
            $imageName = $this->normalizeImageFilename(
                $this->getItemImageName($stock->ItemCode)
            );

            if (!$imageName) {
                $imageName = $this->normalizeImageFilename(
                    $stock->ItemImage ?? $stock->IImage ?? null
                );
            }

            $stock->ImageName = $imageName;
            $stock->ImageUrl = $this->apiImageProxyUrl($imageName);
            
            // Active location codes find करो
            $activeLocationCodes = [];
            foreach ($locationCodes as $code) {
                $resolvedCol = $this->resolveLocationColumn($code);
                if ($resolvedCol && isset($stock->$resolvedCol) && $stock->$resolvedCol > 0) {
                    $activeLocationCodes[] = $code;
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $stock,
                'locationCodes' => $locationCodes,  // ✅ Dynamic location codes
                'activeLocationCodes' => $activeLocationCodes  // ✅ Only active codes
            ]);
        } catch (\Exception $e) {
            Log::error('Stock Show Error', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $queryText = trim($request->input('q', ''));
            if (strlen($queryText) < 2) {
                return response()->json(['success' => false, 'message' => 'Minimum 2 characters required'], 400);
            }
            
            $locationCodes = $this->getDynamicLocationCodes();
            
            // Base select columns
            $selectColumns = [
                'H.ItemDetID','H.ItemCode','H.CompanyName','H.ItemName','H.ProductName','H.DesignName','H.Color','H.StkQty','H.WHQTY','H.MRP','H.Rate','H.SectionName','H.HsStockID',
                'H.Unit','H.Size','H.ACost','H.AccountID','H.COLLECTIONNAME','H.HSNCODE',
                'H.GST','H.DPEX','H.MU','H.RJCACCOUNTID',
                'I.QualityName','I.SerialNo','I.SupplierName','I.SupplierMobileNo','I.ContactPerson','I.ItemImage','I.IImage',
            ];

            // Dynamic location columns को add करो
            foreach ($locationCodes as $code) {
                $resolvedCol = $this->resolveLocationColumn($code);
                if ($resolvedCol) {
                    $selectColumns[] = "H.$resolvedCol";
                }
            }
            
            $results = DB::table('HSStock as H')
                ->leftJoin('ItemDet as I','H.ItemDetID','=','I.SysItemDetID')
                ->select($selectColumns)
                ->where(function ($q) use ($queryText) {
                    $q->where('H.ItemCode','like',"%{$queryText}%")
                        ->orWhere('H.ItemName','like',"%{$queryText}%")
                        ->orWhere('H.DesignName','like',"%{$queryText}%")
                        ->orWhere('I.SupplierName','like',"%{$queryText}%")
                        ->orWhere('I.SerialNo','like',"%{$queryText}%");
                })->limit(10)->get();

            $imageNameCache = [];
            $results->transform(function ($stock) use (&$imageNameCache) {
                // WHQTY comes directly from HSStock.WHQTY.
                $stock->WHQTY = $stock->WHQTY ?? 0;

                $itemCode = trim((string) ($stock->ItemCode ?? ''));

                if ($itemCode !== '' && !array_key_exists($itemCode, $imageNameCache)) {
                    $imageNameCache[$itemCode] = $this->getItemImageName($itemCode);
                }

                $imageName = $this->normalizeImageFilename(
                    $imageNameCache[$itemCode] ?? null
                );

                if (!$imageName) {
                    $imageName = $this->normalizeImageFilename(
                        $stock->ItemImage ?? $stock->IImage ?? null
                    );
                }

                $stock->ImageName = $imageName;
                $stock->ImageUrl = $this->apiImageProxyUrl($imageName);

                return $stock;
            });

            return response()->json(['success' => true, 'data' => $results, 'locationCodes' => $locationCodes]);
        } catch (\Exception $e) {
            Log::error('Search Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $locationCodes = $this->getDynamicLocationCodes();
            
            $query = $this->stockQuery();
            $query = $this->applyFilters($query, $request);
            $stocks = $query->get();

            if ($stocks->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No data to export'], 400);
            }

            $headers = ['ItemCode','CompanyName','ItemName','ProductName','DesignName','Color','StkQty','WHQTY','MRP','Rate','SectionName','Unit','Size','QualityName','SerialNo','SupplierName','SupplierMobileNo','ContactPerson'];
            
            // Dynamic location columns को add करो
            foreach ($locationCodes as $code) {
                $headers[] = $code;
            }
            
            $csv = "\xEF\xBB\xBF" . implode(',', $headers) . "\n";

            foreach ($stocks as $stock) {
                $row = [];
                foreach ($headers as $header) {
                    $value = $stock->$header ?? '';
                    $value = str_replace('"', '""', $value);
                    $row[] = '"' . $value . '"';
                }
                $csv .= implode(',', $row) . "\n";
            }

            return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="stock_export_' . date('Y-m-d_H-i-s') . '.csv"']);
        } catch (\Exception $e) {
            Log::error('Export Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculate allocation stock from the dynamic warehouse/location columns.
     * Uses the current filters, so Location / Section / Company filters are respected.
     */
    private function calculateAllocationStock(Request $request): float
    {
        $locationCodes = $this->getDynamicLocationCodes();
        $selectedLocation = $request->input('location');

        if ($selectedLocation) {
            $resolvedCol = $this->resolveLocationColumn($selectedLocation);

            if (!$resolvedCol) {
                return 0.0;
            }

            $query = DB::table('HSStock as H')
                ->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID');

            $query = $this->applyFilters($query, $request);

            return (float) ($query->sum("H.$resolvedCol") ?? 0);
        }

        $total = 0.0;

        foreach ($locationCodes as $code) {
            $resolvedCol = $this->resolveLocationColumn($code);

            if (!$resolvedCol) {
                continue;
            }

            $query = DB::table('HSStock as H')
                ->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID');

            $query = $this->applyFilters($query, $request);

            $total += (float) ($query->sum("H.$resolvedCol") ?? 0);
        }

        return round($total, 2);
    }

    public function getStats(Request $request)
    {
        try {
            $statsQuery = DB::table('HSStock as H')->leftJoin('ItemDet as I', 'H.ItemDetID', '=', 'I.SysItemDetID');
            $statsQuery = $this->applyFilters($statsQuery, $request);
            $stats = $statsQuery->selectRaw(
                'COUNT(*) as TotalCount, ' .
                'SUM(CASE WHEN H.StkQty <= 10 THEN 1 ELSE 0 END) as LowStockCount, ' .
                'SUM(CASE WHEN H.StkQty <= 0 THEN 1 ELSE 0 END) as OutOfStockCount, ' .
                'SUM(H.StkQty * H.Rate) as TotalValueSum, ' .
                'SUM(H.StkQty) as TotalQuantity, ' .
                'SUM(H.WHQTY) as TotalWHQTY, ' .
                'SUM(H.MRP) as TotalMRP, ' .
                'SUM(H.Rate) as TotalRate, ' .
                'SUM(H.ACost) as TotalCost'
            )->first();

            $totalItems = (int) ($stats->TotalCount ?? 0);
            $lowStockItems = (int) ($stats->LowStockCount ?? 0);
            $outOfStock = (int) ($stats->OutOfStockCount ?? 0);
            $totalValue = (float) ($stats->TotalValueSum ?? 0);

            return response()->json(['success' => true, 'data' => ['total_items' => $totalItems, 'low_stock' => $lowStockItems, 'out_of_stock' => $outOfStock, 'total_value' => round($totalValue, 2),
                    'total_quantity' => (float) ($stats->TotalQuantity ?? 0),
                     'total_whqty' => (float) ($stats->TotalWHQTY ?? 0),
                    'total_mrp' => (float) ($stats->TotalMRP ?? 0),
                    'total_rate' => (float) ($stats->TotalRate ?? 0),
                    'total_cost' => (float) ($stats->TotalCost ?? 0)
                ]]);
        } catch (\Exception $e) {
            Log::error('Stats Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
