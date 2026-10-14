<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    
    private function getLocationColumns()
    {
        try {
            return Schema::getColumnListing('Location');
        } catch (\Exception $e) {
            Log::error('Error getting Location columns: ' . $e->getMessage());
            return ['LocationID', 'Locationcode', 'LocationName', 'LocationAddress', 'GST', 'Color', 'QRCODE', 'DatabaseName'];
        }
    }

    public function list()
    {
        return view('locations.list');
    }

    /**
     * Get all locations (for dropdown)
     * GET /locations/api/all
     * 
     * Dynamically selects available columns
     */
    public function getAll()
    {
        try {
            $availableColumns = $this->getLocationColumns();
            
            // Columns we want to select (in order of priority)
            $desiredColumns = ['LocationID', 'Locationcode', 'LocationName', 'LocationAddress', 'GST', 'Color', 'QRCODE', 'DatabaseName'];
            
            // Only select columns that exist
            $columnsToSelect = array_intersect($desiredColumns, $availableColumns);
            $columnsToSelect = array_values($columnsToSelect); // Re-index array
            
            // If no columns match, select all
            if (empty($columnsToSelect)) {
                $columnsToSelect = $availableColumns;
            }

            $locations = DB::table('Location')
                ->select($columnsToSelect)
                ->orderBy('LocationName', 'ASC')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $locations,
                'count' => $locations->count(),
                'debug_columns' => $columnsToSelect // Show which columns were selected
            ]);
        } catch (\Exception $e) {
            Log::error('Get All Locations Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching locations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get locations datatable (paginated with search)
     * GET /locations/api/datatable?start=0&length=10&search[value]=LJP
     * 
     * Fixed for SQL Server + Dynamic columns
     */
    public function getDataTable(Request $request)
    {
        try {
            $availableColumns = $this->getLocationColumns();
            
            // Get total count FIRST
            $totalRecords = DB::table('Location')->count();

            // Build query
            $query = DB::table('Location');

            // Search filter - only search in columns that exist
            $searchValue = $request->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue, $availableColumns) {
                    if (in_array('LocationName', $availableColumns)) {
                        $q->orWhere('LocationName', 'LIKE', "%{$searchValue}%");
                    }
                    if (in_array('Locationcode', $availableColumns)) {
                        $q->orWhere('Locationcode', 'LIKE', "%{$searchValue}%");
                    }
                });
            }

            // Get filtered count
            $totalFiltered = $query->count();

            // Pagination parameters
            $start = intval($request->input('start', 0));
            $length = intval($request->input('length', 10));

            // Order by available column
            if (in_array('LocationName', $availableColumns)) {
                $query->orderBy('LocationName', 'ASC');
            } else if (in_array('Locationcode', $availableColumns)) {
                $query->orderBy('Locationcode', 'ASC');
            }

            // SQL Server compatibility:
            // Do not use offset()/limit() because this server generates
            // OFFSET/FETCH syntax which is not supported by the current
            // SQL Server compatibility level. Fetch the filtered rows and
            // paginate the Laravel collection below.
            //
            // Select only available columns
            $desiredColumns = ['LocationID', 'Locationcode', 'LocationName', 'LocationAddress', 'GST', 'Color', 'QRCODE', 'DatabaseName'];
            $columnsToSelect = array_intersect($desiredColumns, $availableColumns);
            $columnsToSelect = array_values($columnsToSelect);
            
            if (!empty($columnsToSelect)) {
                $query->select($columnsToSelect);
            }

            // Get all filtered records first, then paginate in PHP.
            $locations = $query->get();

            $locations = $locations
                ->slice($start, $length)
                ->values();

            // Format data for DataTables
            $data = $locations->map(function ($location, $key) use ($start, $availableColumns) {
                $row = [
                    'DT_RowIndex' => $start + $key + 1,
                    'LocationID' => $location->LocationID ?? null,
                    'Locationcode' => $location->Locationcode ?? '-',
                    'LocationName' => $location->LocationName ?? '-',
                    'LocationAddress' => $location->LocationAddress ?? '-',
                    'GST' => $location->GST ?? null,
                    'GSTDisplay' => (isset($location->GST) && $location->GST !== null && trim((string) $location->GST) !== '')
                        ? trim((string) $location->GST)
                        : '-',
                    'Color' => $location->Color ?? null,
                'DatabaseName' => $location->DatabaseName ?? null,
                    'DatabaseName' => $location->DatabaseName ?? null,
                    'QRCODE' => $location->QRCODE ?? null,
                ];
                return $row;
            });

            return response()->json([
                'success' => true,
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalFiltered,
                'data' => $data,
                'total' => $totalFiltered,
                'debug_columns' => $columnsToSelect // Show which columns were selected
            ]);

        } catch (\Exception $e) {
            Log::error('Location Datatable Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single location by ID
     * GET /locations/api/show/{id}
     */
    public function getLocationById($id)
    {
        try {
            $availableColumns = $this->getLocationColumns();
            
            $desiredColumns = ['LocationID', 'Locationcode', 'LocationName', 'LocationAddress', 'GST', 'Color', 'QRCODE', 'DatabaseName'];
            $columnsToSelect = array_intersect($desiredColumns, $availableColumns);
            $columnsToSelect = array_values($columnsToSelect);

            $query = DB::table('Location')
                ->where('LocationID', $id);
            
            if (!empty($columnsToSelect)) {
                $query->select($columnsToSelect);
            }

            $location = $query->first();

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'LocationID' => $location->LocationID ?? null,
                    'Locationcode' => $location->Locationcode ?? null,
                    'LocationName' => $location->LocationName ?? null,
                    'LocationAddress' => $location->LocationAddress ?? null,
                    'GST' => $location->GST ?? null,
                    'Color' => $location->Color ?? null,
                'DatabaseName' => $location->DatabaseName ?? null,
                    'DatabaseName' => $location->DatabaseName ?? null,
                    'QRCODE' => $location->QRCODE ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Location By ID Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save location (Create or Update)
     * POST /locations/api/save
     */
    public function saveLocation(Request $request)
    {
        try {
            $isEdit = !empty($request->input('LocationID'));

            // Validation rules
            $rules = [
                'LocationName' => 'required|string|max:255',
                'LocationAddress' => 'nullable|string|max:500',
                'GST' => 'nullable|string|max:50',
                'Color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'DatabaseName' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]+$/']
            ];

            if ($isEdit) {
                $rules['LocationID'] = 'required|integer|exists:Location,LocationID';
            } else {
                $rules['Locationcode'] = 'required|string|max:50|unique:Location,Locationcode';
            }

            // Validate
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $availableColumns = $this->getLocationColumns();

            // Build data array with only available columns
            $data = [];
            
            if (in_array('LocationName', $availableColumns)) {
                $data['LocationName'] = $request->input('LocationName');
            }
            if (in_array('LocationAddress', $availableColumns)) {
                $data['LocationAddress'] = $request->input('LocationAddress') ?? null;
            }
            if (in_array('GST', $availableColumns)) {
                $data['GST'] = $request->input('GST') ?? null;
            }
            if (in_array('Color', $availableColumns)) {
                $data['Color'] = $request->input('Color') ?? null;
            }
            if (in_array('DatabaseName', $availableColumns)) {
                $db = strtoupper(trim((string) $request->input('DatabaseName', '')));
                $data['DatabaseName'] = $db !== '' ? $db : null;
            }

            \Illuminate\Support\Facades\Cache::forget('user_access.locations.all');
            \Illuminate\Support\Facades\Cache::forget('floor_sales.locations');
            // QRCODE is a VARBINARY column in SQL Server.
            // Do not send an empty/string value from the form, otherwise
            // SQL Server tries to convert varchar(max) to varbinary(max).
            // Keep the existing QR value during update; insert will use DB default/NULL.

            if ($isEdit) {
                // UPDATE
                $locationId = $request->input('LocationID');

                DB::table('Location')
                    ->where('LocationID', $locationId)
                    ->update($data);

                Log::info("✅ Location updated: ID={$locationId}, Data=" . json_encode($data));

                return response()->json([
                    'success' => true,
                    'message' => 'Location updated successfully',
                    'LocationID' => $locationId
                ]);
            } else {
                // CREATE
                if (in_array('Locationcode', $availableColumns)) {
                    $data['Locationcode'] = $request->input('Locationcode');
                }

                $locationId = DB::table('Location')->insertGetId($data);

                Log::info("✅ Location created: ID={$locationId}, Data=" . json_encode($data));

                return response()->json([
                    'success' => true,
                    'message' => 'Location created successfully',
                    'LocationID' => $locationId
                ], 201);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Save Location Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete location
     * DELETE /locations/api/delete/{id}
     */
    public function deleteLocation($id)
    {
        try {
            // Check if location exists
            $location = DB::table('Location')
                ->where('LocationID', $id)
                ->first();

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found'
                ], 404);
            }

            // Check if location is used in HSStock
            $usedCount = DB::table('HSStock')
                ->where(function ($query) use ($location) {
                    $query->where('LOCATION', $location->LocationID)
                          ->orWhere('LOCATION', $location->Locationcode ?? null);
                })
                ->count();

            if ($usedCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete: Location is linked to {$usedCount} stock items"
                ], 422);
            }

            // Delete
            DB::table('Location')
                ->where('LocationID', $id)
                ->delete();

            Log::info("✅ Location deleted: ID={$id}");

            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Location Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get location codes only
     * GET /locations/api/codes
     */
    public function getLocationCodes()
    {
        try {
            $availableColumns = $this->getLocationColumns();
            
            $query = DB::table('Location');
            
            if (in_array('Locationcode', $availableColumns)) {
                $query->whereNotNull('Locationcode')
                      ->where('Locationcode', '<>', '');
            }
            
            $codes = $query->orderBy('Locationcode')->pluck('Locationcode')->toArray();

            return response()->json([
                'success' => true,
                'codes' => $codes,
                'count' => count($codes)
            ]);
        } catch (\Exception $e) {
            Log::error('Get Location Codes Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching location codes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get GST rates (all locations with GST)
     * GET /locations/api/gst-rates
     */
    public function getGstRates()
    {
        try {
            $availableColumns = $this->getLocationColumns();
            
            if (!in_array('GST', $availableColumns)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'GST column not available',
                    'count' => 0
                ]);
            }

            $rates = DB::table('Location')
                ->select('LocationID', 'Locationcode', 'LocationName', 'GST')
                ->whereNotNull('GST')
                ->where('GST', '>', 0)
                ->orderBy('GST', 'DESC')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $rates,
                'count' => $rates->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Get GST Rates Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching GST rates'
            ], 500);
        }
    }

    /**
     * Get color badges
     * GET /locations/api/color-badges
     */
    public function getColorBadges()
    {
        try {
            $availableColumns = $this->getLocationColumns();
            
            if (!in_array('Color', $availableColumns)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Color column not available',
                    'count' => 0
                ]);
            }

            $colors = DB::table('Location')
                ->select('LocationID', 'Locationcode', 'LocationName', 'Color')
                ->whereNotNull('Color')
                ->where('Color', '<>', '')
                ->orderBy('LocationName')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $colors,
                'count' => $colors->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Get Color Badges Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching color badges'
            ], 500);
        }
    }

    /**
     * DEBUG: Show Location table structure
     * GET /locations/api/debug/structure
     */
    public function debugStructure()
    {
        try {
            $columns = $this->getLocationColumns();
            
            $sampleRow = DB::table('Location')->first();
            
            return response()->json([
                'success' => true,
                'table' => 'Location',
                'columns' => $columns,
                'column_count' => count($columns),
                'sample_row' => $sampleRow,
                'has_GST' => in_array('GST', $columns),
                'has_Color' => in_array('Color', $columns),
                'has_Locationcode' => in_array('Locationcode', $columns),
                'has_LocationName' => in_array('LocationName', $columns),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}