<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\UserFieldPermission;
use App\Services\UserAccessService;

class ItemStockController extends Controller
{
    /**
     * Admin gets every ItemStockUpdateDis field automatically.
     * Normal users only get fields saved in user_field_permissions.
     */
    private function isAdmin(): bool
    {
        $user = auth()->user();

        return $user && (int) ($user->user_role ?? 0) === 1;
    }

    /**
     * Item Stock field access from the User form (config/user_access.php).
     * Old names are mapped: MRP -> mrp, Discount -> discount, Image -> image_upload.
     */
    private function canViewItemStockField(string $fieldName): bool
    {
        $map = [
            'mrp' => 'mrp',
            'discount' => 'discount',
            'image' => 'image_upload',
        ];
        $key = $map[strtolower($fieldName)] ?? strtolower($fieldName);

        return UserAccessService::canField('item-stock', $key);
    }

    /**
     * ⚡ DYNAMIC: Get column mapping from Location table
     */
    private function getLocationColumnMapping()
    {
        return Cache::rememberForever('location_column_mapping', function () {
            $locations = DB::table('Location')
                ->whereNotNull('Locationcode')
                ->where('Locationcode', '<>', '')
                ->orderBy('Locationcode')
                ->select('LocationID', 'Locationcode', 'LocationName', 'Color')
                ->get();

            $hsStockColumns = Schema::getColumnListing('HSStock');
            $columnMap = [];

            foreach ($locations as $location) {
                $code = strtoupper($location->Locationcode);

                foreach ($hsStockColumns as $column) {
                    if (strtoupper($column) === $code) {
                        $columnMap[] = [
                            'locationId' => $location->LocationID,
                            'locationCode' => $location->Locationcode,
                            'locationName' => $location->LocationName,
                            'color' => $location->Color,
                            'hsStockColumn' => $column,
                            'isLinked' => true,
                        ];
                        break;
                    }
                }
            }

            return $columnMap;
        });
    }

    /**
     * ⚡ OPTIMIZED: Search item with DYNAMIC location fields
     */
    /**
     * ⚡ OPTIMIZED: Search item - Simple location format
     */
    public function searchItem(Request $request)
    {
        try {
            $itemCode = $request->input('itemCode');

            if (! $itemCode) {
                return response()->json(['success' => false, 'message' => 'Item code required'], 400);
            }

            // Same data source/priority as WEB controller.
            // Avoid SELECT * with joins because duplicate columns can overwrite
            // HSStock values with NULL/0 values from joined tables.
            $sql = "SELECT TOP 1
                H.*,
                ISNULL(U.Discount, 0) AS __ItemStockUpdateDiscount,
                I.QualityName,
                I.SupplierName,
                I.SupplierMobileNo,
                I.ContactPerson,
                I.ItemImage,
                I.IImage
            FROM HSStock H
            LEFT JOIN ItemDet I ON H.ItemDetID = I.SysItemDetID
            LEFT JOIN ItemStockUpdateDis U ON H.ItemCode = U.ItemCode
            WHERE H.ItemCode = ?";

            $stock = DB::selectOne($sql, [$itemCode]);

            if ($stock) {
                $stock->Discount = $stock->__ItemStockUpdateDiscount ?? 0;
                unset($stock->__ItemStockUpdateDiscount);
            }

            if (! $stock) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            // ✅ Get location mapping
            $locationMapping = $this->getLocationColumnMapping();

            // ✅ Build dynamic branch stock data from Location table.
            // Existing branchWiseStock (CODE => QTY) is kept for backward compatibility.
            // New branchWiseStockDetails also includes location name + color.
            $locationQuantities = [];
            $branchWiseStockDetails = [];
            $locationColors = [];

            // Branch-wise: only the locations this user is allowed to see.
            $allowedCodes = UserAccessService::allowedLocationCodes();

            foreach ($locationMapping as $mapping) {
                if ($allowedCodes !== null
                    && !in_array(strtoupper(trim((string) $mapping['locationCode'])), $allowedCodes, true)) {
                    continue;
                }
                $columnName = $mapping['hsStockColumn'];
                $locationCode = $mapping['locationCode'];

                $quantity = 0;
                if (isset($stock->$columnName)) {
                    $quantity = floatval($stock->$columnName);
                }

                $locationQuantities[$locationCode] = $quantity;
                $locationColors[$locationCode] = $mapping['color'] ?? null;

                $branchWiseStockDetails[] = [
                    'locationId' => $mapping['locationId'] ?? null,
                    'locationCode' => $locationCode,
                    'locationName' => $mapping['locationName'] ?? null,
                    'color' => $mapping['color'] ?? null,
                    'quantity' => $quantity,
                    'hsStockColumn' => $columnName,
                    'isLinked' => (bool) ($mapping['isLinked'] ?? true),
                ];
            }

            $canViewMrp = $this->canViewItemStockField('MRP');
            $canViewDiscount = $this->canViewItemStockField('Discount');
            $canViewImage = true; // viewing is allowed; upload needs 'image_upload'
            $access = UserAccessService::fieldMap('item-stock');

            $acost = floatval($stock->ACost ?? 0);

            // ✅ Base response data. Restricted ItemStockUpdateDis fields
            // are added only when the logged-in user has permission.
            $responseData = [
                'ItemDetID' => $stock->ItemDetID ?? null,
                'ItemCode' => $stock->ItemCode ?? null,
                'CompanyName' => $stock->CompanyName ?? null,
                'ItemName' => $stock->ItemName ?? null,
                'ProductName' => $stock->ProductName ?? null,
                'DesignName' => $stock->DesignName ?? null,
                'Color' => $stock->Color ?? null,
                'StkQty' => floatval($stock->StkQty ?? 0),
                'WHQTY' => floatval($stock->WHQTY ?? 0),
                'Rate' => floatval($stock->Rate ?? 0),
                'ACost' => $acost,
                'Unit' => trim($stock->Unit ?? ''),
                'Size' => $stock->Size ?? null,
                'SectionName' => $stock->SectionName ?? null,
                'GST' => floatval($stock->GST ?? 0),
                'HSNCODE' => trim($stock->HSNCODE ?? ''),
                'MU' => floatval($stock->MU ?? 0),
                'MD' => floatval($stock->MD ?? 0),
                'DPEX' => floatval($stock->DPEX ?? 0),
                'QualityName' => $stock->QualityName ?? null,
                'SupplierName' => $stock->SupplierName ?? null,
                'SupplierMobileNo' => $stock->SupplierMobileNo ?? null,
                'ContactPerson' => $stock->ContactPerson ?? null,

                // ✅ BRANCH-WISE STOCK
                'branchWiseStock' => $locationQuantities,
                'branchWiseStockDetails' => $branchWiseStockDetails,
                'locationColors' => $locationColors,
            ];

            if ($canViewMrp) {
                $responseData['MRP'] = floatval($stock->MRP ?? 0);
            }

            if (!empty($access['markup'])) {
                $responseData['Markup'] = isset($stock->MU)
                    ? round((float) $stock->MU, 2)
                    : 0;
            }

            // Remove fields this user may not see
            $hide = [
                'supplier_name' => ['SupplierName'],
                'supplier_mobile' => ['SupplierMobileNo'],
                'contact_person' => ['ContactPerson'],
                'markup' => ['MU', 'Markup'],
                'markdown' => ['MD'],
                'dp_exclusive' => ['DPEX'],
            ];
            foreach ($hide as $key => $props) {
                if (empty($access[$key])) {
                    foreach ($props as $prop) {
                        unset($responseData[$prop]);
                    }
                }
            }

            if ($canViewDiscount) {
                $responseData['Discount'] = floatval($stock->Discount ?? 0);
            }

            if ($canViewImage) {
                $resolvedImageUrl = $this->resolveImageUrl($stock);
                $responseData['Image'] = $resolvedImageUrl;
                $responseData['ImageUrl'] = $resolvedImageUrl;
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
                // App uses this to show/hide fields and the Save MRP / Save Discount buttons
                'permissions' => [
                    'fields' => $access,
                    'can_edit_mrp' => !empty($access['mrp']),
                    'can_edit_discount' => !empty($access['discount']),
                    'can_upload_image' => !empty($access['image_upload']),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Search Item Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    private function resolveImageUrl($stock)
    {
        if (empty($stock->ItemCode)) {
            return null;
        }

        $localImage = $this->findLocalImage($stock->ItemCode);
        if ($localImage) {
            return $localImage;
        }

        if (!empty($stock->ItemImage)) {
            return $stock->ItemImage;
        }

        if (!empty($stock->IImage)) {
            return $stock->IImage;
        }

        $imageName = $this->getItemImageName($stock->ItemCode);
        if ($imageName) {
            return route('stock.imageProxy', ['filename' => $imageName]);
        }

        return null;
    }

    private function findLocalImage($itemCode)
    {
        $folder = public_path('items/' . $itemCode);

        if (!is_dir($folder)) {
            return null;
        }

        $files = array_filter(glob($folder . '/*'), 'is_file');

        if (empty($files)) {
            return null;
        }

        $filename = basename(reset($files));

        return route('stock.imageProxy', ['filename' => $filename]);
    }

    private function getItemImageName($itemCode)
    {
        try {
            $result = DB::select("EXEC dbo.ProcItemCodeDet @ItemCode = ?", [$itemCode]);

            if (!empty($result)) {
                return $result[0]->ImageName ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Item Image fetch error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * ✅ Get all location codes with mapping
     */
    public function getLocationMapping()
    {
        try {
            $mapping = $this->getLocationColumnMapping();

            return response()->json([
                'success' => true,
                'data' => $mapping,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ⚡ OPTIMIZED: Permissions
     */
    public function getPermissions(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'can_edit_mrp' => $this->canViewItemStockField('MRP'),
                    'can_edit_discount' => $this->canViewItemStockField('Discount'),
                    'can_upload_image' => $this->canViewItemStockField('Image'),
                    'can_download_pdf' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ⚡ OPTIMIZED: Upload image
     */
    public function uploadImage(Request $request)
    {
        try {
            if (!$this->canViewItemStockField('Image')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to upload item images.',
                ], 403);
            }

            $request->validate([
                'itemCode' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            $itemCode = $request->input('itemCode');
            $image = $request->file('image');

            $folder = public_path('items/'.$itemCode);
            if (! is_dir($folder)) {
                mkdir($folder, 0755, true);
            }

            foreach (glob($folder.'/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            $filename = $itemCode.'.'.$image->getClientOriginalExtension();
            $image->move($folder, $filename);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded',
                'filename' => $filename,
                'url' => route('stock.imageProxy', ['filename' => $filename]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error uploading image'], 500);
        }
    }

    /**
     * ⚡ OPTIMIZED: Update MRP
     */
    public function updateMrp(Request $request)
    {
        try {
            if (!$this->canViewItemStockField('MRP')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view or update MRP.',
                ], 403);
            }

            $request->validate([
                'itemCode' => 'required|string',
                'mrp' => 'required|numeric|min:0',
            ]);

            $itemCode = $request->input('itemCode');
            $mrp = $request->input('mrp');

            $stock = DB::selectOne(
                'SELECT ItemDetID FROM HSStock WHERE ItemCode = ?',
                [$itemCode]
            );

            if (! $stock) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            DB::table('HSStock')
                ->where('ItemCode', $itemCode)
                ->update(['MRP' => $mrp]);

            $existing = DB::table('ItemStockUpdateDis')
                ->where('ItemCode', $itemCode)
                ->first();

            if ($existing) {
                DB::table('ItemStockUpdateDis')
                    ->where('ItemCode', $itemCode)
                    ->update(['MRP' => $mrp, 'ItemDetID' => $stock->ItemDetID]);
            } else {
                DB::table('ItemStockUpdateDis')->insert([
                    'ItemCode' => $itemCode,
                    'ItemDetID' => $stock->ItemDetID,
                    'MRP' => $mrp,
                    'Discount' => 0,
                ]);
            }

            Cache::forget('location_column_mapping');

            return response()->json(['success' => true, 'message' => 'MRP updated']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ⚡ OPTIMIZED: Update Discount
     */
    public function updateDiscount(Request $request)
    {
        try {
            if (!$this->canViewItemStockField('Discount')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view or update Discount.',
                ], 403);
            }

            $request->validate([
                'itemCode' => 'required|string',
                'discount' => 'required|numeric|min:0',
            ]);

            $itemCode = $request->input('itemCode');
            $discount = $request->input('discount');

            $stock = DB::selectOne(
                'SELECT ItemDetID FROM HSStock WHERE ItemCode = ?',
                [$itemCode]
            );

            if (! $stock) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            $existing = DB::table('ItemStockUpdateDis')
                ->where('ItemCode', $itemCode)
                ->first();

            if ($existing) {
                DB::table('ItemStockUpdateDis')
                    ->where('ItemCode', $itemCode)
                    ->update(['Discount' => $discount, 'ItemDetID' => $stock->ItemDetID]);
            } else {
                DB::table('ItemStockUpdateDis')->insert([
                    'ItemCode' => $itemCode,
                    'ItemDetID' => $stock->ItemDetID,
                    'MRP' => 0,
                    'Discount' => $discount,
                ]);
            }

            Cache::forget('location_column_mapping');

            return response()->json(['success' => true, 'message' => 'Discount updated']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ⚡ OPTIMIZED: Download PDF
     */
    public function downloadPdf(Request $request)
    {
        try {
            $itemCode = $request->input('itemCode');
            if (! $itemCode) {
                return response()->json(['success' => false, 'message' => 'Item code required'], 400);
            }

            $sql = "SELECT TOP 1
                        H.*,
                        ISNULL(U.Discount, 0) AS __ItemStockUpdateDiscount,
                        I.QualityName,
                        I.SupplierName,
                        I.SupplierMobileNo,
                        I.ContactPerson,
                        I.ItemImage,
                        I.IImage
                    FROM HSStock H
                    LEFT JOIN ItemDet I ON H.ItemDetID = I.SysItemDetID
                    LEFT JOIN ItemStockUpdateDis U ON H.ItemCode = U.ItemCode
                    WHERE H.ItemCode = ?";

            $stock = DB::selectOne($sql, [$itemCode]);

            if ($stock) {
                $stock->Discount = $stock->__ItemStockUpdateDiscount ?? 0;
                unset($stock->__ItemStockUpdateDiscount);

                if ($this->canViewItemStockField('Image')) {
                    $stock->ImageUrl = $this->resolveImageUrl($stock);
                }

                if ($this->canViewItemStockField('MRP')) {
                    $stock->Markup = isset($stock->MU)
                        ? (float) $stock->MU
                        : 0;
                }
            }

            if (! $stock) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            // PDF must follow the same ItemStockUpdateDis permissions.
            if (!$this->canViewItemStockField('MRP')) {
                unset($stock->MRP);
            }

            if (!$this->canViewItemStockField('Discount')) {
                unset($stock->Discount);
            }

            if (!$this->canViewItemStockField('Image')) {
                unset($stock->Image);
                unset($stock->ItemImage);
                unset($stock->IImage);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('itemstock.pdf', [
                'stock' => $stock,
                'locationMapping' => $this->getLocationColumnMapping(),
            ]);

            return $pdf->download("ItemStock_{$itemCode}.pdf");
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    public function list()
    {
        return view('itemstock.list');
    }
}
