<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\UserFieldPermission;
use App\Services\UserAccessService;

class ItemStockController extends Controller
{
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
     * Remove the values this user may not see before they leave the server.
     */
    private function applyItemStockFieldPermissions(object $stock): object
    {
        $hide = [
            'supplier_name' => ['SupplierName'],
            'supplier_mobile' => ['SupplierMobileNo'],
            'contact_person' => ['ContactPerson'],
            'markup' => ['Markup', 'MU'],
            'markdown' => ['MD', 'Markdown'],
            'discount' => ['Discount'],
            'mrp' => ['MRP'],
        ];

        foreach ($hide as $key => $props) {
            if (!UserAccessService::canField('item-stock', $key)) {
                foreach ($props as $prop) {
                    unset($stock->{$prop});
                }
            }
        }

        return $stock;
    }

    /** Location codes (branch columns) this user may see. */
    private function allowedBranchFilter(array $codes): array
    {
        $allowed = UserAccessService::allowedLocationCodes();
        if ($allowed === null) {
            return $codes;
        }
        return array_values(array_filter($codes, fn ($c) => in_array(strtoupper(trim((string) $c)), $allowed, true)));
    }

    
    public function list()
    {
        return view('itemstock.list', [
            'itemAccess' => UserAccessService::fieldMap('item-stock'),
        ]);
    }

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
                    'fields' => UserAccessService::fieldMap('item-stock')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function searchItem(Request $request)
    {
        try {
            $itemCode = $request->input('itemCode');

            if (!$itemCode) {
                return response()->json(['success' => false, 'message' => 'Item code is required'], 400);
            }

            try {
                $sql = "SELECT TOP 1
                    H.*,
                    ISNULL(U.Discount, 0) AS __ItemStockUpdateDiscount,
                    I.QualityName, I.SupplierName, I.SupplierMobileNo, I.ContactPerson,
                    I.ItemImage, I.IImage
                FROM HSStock H
                LEFT JOIN ItemDet I ON H.ItemDetID = I.SysItemDetID
                LEFT JOIN ItemStockUpdateDis U ON H.ItemCode = U.ItemCode
                WHERE H.ItemCode = ?";
                
                $stock = DB::selectOne($sql, [$itemCode]);

                if ($stock) {
                    // Keep the existing frontend contract.
                    $stock->Discount = $stock->__ItemStockUpdateDiscount ?? 0;
                    unset($stock->__ItemStockUpdateDiscount);
                }
            } catch (\Exception $e) {
                Log::error('HSStock Query Error: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Error fetching stock data: ' . $e->getMessage()], 500);
            }

            if (!$stock) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            // Use stored MU as MARKUP so web value matches the required data.
            if ($this->canViewItemStockField('markup')) {
                $stock->Markup = isset($stock->MU)
                    ? (float) $stock->MU
                    : 0;
            }

            // Image is visible to everyone with the module; uploading needs 'image_upload'.
            $stock->ImageUrl = $this->resolveImageUrl($stock);

            $stock = $this->applyItemStockFieldPermissions($stock);

            // Return location code + configured color for branch-wise stock display.
            $locations = DB::table('Location')
                ->whereNotNull('Locationcode')
                ->where('Locationcode', '<>', '')
                ->select('Locationcode', 'Color')
                ->orderBy('Locationcode')
                ->get()
                ->map(function ($location) {
                    return [
                        'code' => $location->Locationcode,
                        'color' => $location->Color,
                    ];
                })
                ->values()
                ->toArray();

            // Branch-wise: only the locations this user is allowed to see.
            $allCodes = array_column($locations, 'code');
            $allowedCodes = $this->allowedBranchFilter($allCodes);
            foreach (array_diff($allCodes, $allowedCodes) as $hiddenCode) {
                unset($stock->{$hiddenCode});
            }
            $locations = array_values(array_filter($locations, fn ($l) => in_array($l['code'], $allowedCodes, true)));

            // Keep locationCodes for backward compatibility.
            $locationCodes = array_values(array_map(
                fn ($location) => $location['code'],
                $locations
            ));

            $locationColors = [];
            foreach ($locations as $location) {
                $code = strtoupper(trim((string) ($location['code'] ?? '')));
                if ($code !== '') {
                    $locationColors[$code] = $location['color'] ?? null;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $stock,
                'locationCodes' => $locationCodes,
                'locations' => $locations,
                'locationColors' => $locationColors,
            ]);
        } catch (\Exception $e) {
            Log::error('Search Item Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function resolveImageUrl($stock)
    {
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

    public function uploadImage(Request $request)
    {
        try {
            if (!$this->canViewItemStockField('Image')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to upload item images.'
                ], 403);
            }

            $request->validate([
                'itemCode' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);

            $itemCode = $request->input('itemCode');
            $image = $request->file('image');

            if (!$image) {
                return response()->json(['success' => false, 'message' => 'Image file required'], 400);
            }

            $relativeFolder = 'items/' . $itemCode;
            $absoluteFolder = public_path($relativeFolder);

            if (!is_dir($absoluteFolder)) {
                mkdir($absoluteFolder, 0755, true);
            }
            foreach (glob($absoluteFolder . '/*') as $existingFile) {
                if (is_file($existingFile)) {
                    unlink($existingFile);
                }
            }

            $filename = $itemCode . '.' . $image->getClientOriginalExtension();
            $image->move($absoluteFolder, $filename);

            $path = $relativeFolder . '/' . $filename;
            $url = route('stock.imageProxy', ['filename' => $filename]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'filename' => $filename,
                'path' => $path,
                'url' => $url
            ]);
        } catch (\Exception $e) {
            Log::error('Upload Image Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error uploading image'], 500);
        }
    }

    public function updateMrp(Request $request)
    {
        try {
            if (!$this->canViewItemStockField('MRP')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view or update MRP.'
                ], 403);
            }

            $request->validate([
                'itemCode' => 'required|string',
                'mrp' => 'required|numeric|min:0'
            ]);

            $itemCode = $request->input('itemCode');
            $mrp = $request->input('mrp');

            $stock = DB::selectOne(
                "SELECT H.ItemDetID FROM HSStock H WHERE H.ItemCode = ?",
                [$itemCode]
            );

            if (!$stock) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            $itemDetID = $stock->ItemDetID;

            DB::table('HSStock')
                ->where('ItemCode', $itemCode)
                ->update(['MRP' => $mrp]);

            $existing = DB::table('ItemStockUpdateDis')
                ->where('ItemCode', $itemCode)
                ->first();

            if ($existing) {
                DB::table('ItemStockUpdateDis')
                    ->where('ItemCode', $itemCode)
                    ->update([
                        'MRP' => $mrp,
                        'ItemDetID' => $itemDetID
                    ]);
            } else {
                DB::table('ItemStockUpdateDis')->insert([
                    'ItemCode' => $itemCode,
                    'ItemDetID' => $itemDetID,
                    'MRP' => $mrp,
                    'Discount' => 0
                ]);
            }

            return response()->json(['success' => true, 'message' => 'MRP updated successfully in both tables']);
        } catch (\Exception $e) {
            Log::error('Update MRP Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating MRP: ' . $e->getMessage()], 500);
        }
    }

    public function updateDiscount(Request $request)
    {
        try {
            if (!$this->canViewItemStockField('Discount')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view or update Discount.'
                ], 403);
            }

            $request->validate([
                'itemCode' => 'required|string',
                'discount' => 'required|numeric|min:0'
            ]);

            $itemCode = $request->input('itemCode');
            $discount = $request->input('discount');

            $stock = DB::selectOne(
                "SELECT H.ItemDetID FROM HSStock H WHERE H.ItemCode = ?",
                [$itemCode]
            );

            if (!$stock) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            $itemDetID = $stock->ItemDetID;

            $existing = DB::table('ItemStockUpdateDis')
                ->where('ItemCode', $itemCode)
                ->first();

            if ($existing) {
                DB::table('ItemStockUpdateDis')
                    ->where('ItemCode', $itemCode)
                    ->update([
                        'Discount' => $discount,
                        'ItemDetID' => $itemDetID
                    ]);
            } else {
                DB::table('ItemStockUpdateDis')->insert([
                    'ItemCode' => $itemCode,
                    'ItemDetID' => $itemDetID,
                    'MRP' => 0,
                    'Discount' => $discount
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Discount updated successfully']);
        } catch (\Exception $e) {
            Log::error('Update Discount Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating discount: ' . $e->getMessage()], 500);
        }
    }

    public function downloadPdf(Request $request)
    {
        try {
            $itemCode = $request->input('itemCode');

            if (!$itemCode) {
                return redirect()->back()->with('error', 'Item code required');
            }

            try {
                $sql = "SELECT TOP 1
                    H.*,
                    ISNULL(U.Discount, 0) AS __ItemStockUpdateDiscount,
                    I.QualityName, I.SupplierName, I.SupplierMobileNo, I.ContactPerson,
                    I.ItemImage, I.IImage
                FROM HSStock H
                LEFT JOIN ItemDet I ON H.ItemDetID = I.SysItemDetID
                LEFT JOIN ItemStockUpdateDis U ON H.ItemCode = U.ItemCode
                WHERE H.ItemCode = ?";
                
                $stock = DB::selectOne($sql, [$itemCode]);

                if ($stock) {
                    // Keep the existing frontend contract.
                    $stock->Discount = $stock->__ItemStockUpdateDiscount ?? 0;
                    unset($stock->__ItemStockUpdateDiscount);
                }
            } catch (\Exception $e) {
                Log::error('Fetch Item Error: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Error fetching item details');
            }

            if (!$stock) {
                return redirect()->back()->with('error', 'Item not found');
            }

            // Use stored MU as MARKUP in PDF as well.
            if ($this->canViewItemStockField('markup')) {
                $stock->Markup = isset($stock->MU)
                    ? (float) $stock->MU
                    : 0;
            }

            // Image is visible to everyone with the module; uploading needs 'image_upload'.
            $stock->ImageUrl = $this->resolveImageUrl($stock);

            $stock = $this->applyItemStockFieldPermissions($stock);

            try {
                $pdf = PDF::loadView('itemstock.pdf', [
                    'stock' => $stock,
                    'access' => UserAccessService::fieldMap('item-stock'),
                    'locationCodes' => $this->allowedBranchFilter(DB::table('Location')
                        ->whereNotNull('Locationcode')
                        ->where('Locationcode', '<>', '')
                        ->orderBy('Locationcode')
                        ->pluck('Locationcode')
                        ->toArray()),
                    'locations' => DB::table('Location')
                        ->whereNotNull('Locationcode')
                        ->where('Locationcode', '<>', '')
                        ->select('Locationcode', 'Color')
                        ->orderBy('Locationcode')
                        ->get()
                ]);
                return $pdf->download('ItemStockReport_' . $itemCode . '_' . date('Y-m-d') . '.pdf');
            } catch (\Exception $pdfException) {
                Log::warning('PDF generation failed, returning view instead: ' . $pdfException->getMessage());
                return view('itemstock.pdf', ['stock' => $stock, 'access' => UserAccessService::fieldMap('item-stock')]);
            }
        } catch (\Exception $e) {
            Log::error('Download PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }
}