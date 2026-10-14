<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\StockController;
use App\Http\Controllers\API\UploadGateEntryBillController;
use App\Http\Controllers\API\HomeStayController;
use App\Http\Controllers\API\PendingRentController;
use App\Http\Controllers\API\StudentDetailsController;
use App\Http\Controllers\API\BedOccupancyController;
use App\Http\Controllers\API\NewStudentController;
use App\Http\Controllers\API\ItemStockController;
use App\Http\Controllers\API\DocumentDownloadController;
use App\Http\Controllers\API\GateEntryController;
use App\Http\Controllers\API\DailyCollectionController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\FloorWiseSalesController;
use App\Http\Controllers\API\GRNController;
use App\Http\Controllers\API\LocationController;



    // =====================
    // PUBLIC AUTH ROUTES
    // =====================
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/auth/otp/send', [AuthController::class, 'sendOtp']);
        Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp']);
    });
    Route::post('/auth/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('/auth/reset-password', [AuthController::class, 'reset']);

    // =====================
    // PROTECTED ROUTES
    // =====================
    Route::middleware(['auth:sanctum', 'field.permissions'])->group(function () {

        // --------- AUTH ---------
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/password/change', [AuthController::class, 'changePassword']);

        // --------- DASHBOARD ---------
        Route::get('/dashboard', [DashboardController::class, 'dashboard']);

        // --------- USERS (Admin Only) ---------
        Route::middleware('admin')->group(function () {
            Route::get('/users/datatable', [UserController::class, 'getDataTable']);
            Route::get('/users/access-options', [UserController::class, 'accessOptions']);
            Route::get('/users/permission-tables', [UserController::class, 'getPermissionTables']);
            Route::get('/users/table-fields', [UserController::class, 'getTableFields']);
            Route::get('/users/{id}/field-permissions', [UserController::class, 'getUserFieldPermissions'])->whereNumber('id');
            Route::post('/users/save', [UserController::class, 'saveUser']);
            Route::delete('/users/{id}', [UserController::class, 'deleteUser'])->whereNumber('id');
            Route::get('/users/{id}', [UserController::class, 'getUser'])->whereNumber('id');
        });

        // --------- STOCK ---------
        Route::prefix('stock')->group(function () {
            Route::get('/search', [StockController::class, 'search']);
            Route::get('/export', [StockController::class, 'export']);
            Route::get('/datatable', [StockController::class, 'getDataTable']);
            Route::get('/stats', [StockController::class, 'getStats']);
            Route::get('/companies-by-section', [StockController::class, 'getCompaniesBySection']);
            Route::get('/sections-by-location', [StockController::class, 'getSectionsByLocation']);
            Route::get('/filter-options', [StockController::class, 'getFilterOptions']);
            Route::get('/all-options', [StockController::class, 'allOptions']);
            Route::get('/image-proxy/{filename}', [StockController::class, 'imageProxy']);
            Route::get('/item-locations', [StockController::class, 'itemLocations']);
            Route::get('/supplier-locations', [StockController::class, 'supplierLocations']);
            Route::get('/{id}', [StockController::class, 'show'])->whereNumber('id');
            
        });

        // --------- ITEM STOCK ---------
        Route::prefix('itemstock')->group(function () {
            Route::get('/permissions', [ItemStockController::class, 'getPermissions']);
            Route::get('/location-mapping', [ItemStockController::class, 'getLocationMapping']);
            Route::post('/search', [ItemStockController::class, 'searchItem']);
            Route::post('/upload-image', [ItemStockController::class, 'uploadImage']);
            Route::post('/update-mrp', [ItemStockController::class, 'updateMrp']);
            Route::post('/update-discount', [ItemStockController::class, 'updateDiscount']);
            Route::get('/download-pdf', [ItemStockController::class, 'downloadPdf']);
        });

        // --------- UPLOAD GATE ENTRY BILL ---------
        Route::prefix('uploadgateentrybill')->group(function () {
            Route::get('/datatable', [UploadGateEntryBillController::class, 'datatable']);
            Route::post('/save', [UploadGateEntryBillController::class, 'save']);
            Route::get('/{id}', [UploadGateEntryBillController::class, 'show']);
            Route::post('/{id}/approve', [UploadGateEntryBillController::class, 'approve']);
            Route::post('/{id}/reject', [UploadGateEntryBillController::class, 'reject']);
            Route::post('/{id}/update-status', [UploadGateEntryBillController::class, 'updateStatus']);
            Route::delete('/{id}', [UploadGateEntryBillController::class, 'delete']);
        });

        // --------- HOME STAY ---------
        
        Route::prefix('homestay')->group(function () {
            Route::get('/pending-rent/datatable', [PendingRentController::class, 'getDataTable']);
            Route::get('/student-details/datatable', [StudentDetailsController::class, 'getDataTable']);
            Route::get('/bed-occupancy/datatable', [BedOccupancyController::class, 'getDataTable']);
            Route::get('/new-student/datatable', [NewStudentController::class, 'getDataTable']);
        });

        // --------- DOCUMENT DOWNLOAD ---------
        Route::prefix('documentdownload')->group(function () {
            Route::get('/locations', [DocumentDownloadController::class, 'getLocations']);
            Route::get('/doc-types', [DocumentDownloadController::class, 'getDocTypes']);
            Route::get('/data', [DocumentDownloadController::class, 'getListData']);
            Route::get('/detail', [DocumentDownloadController::class, 'detail']);
            Route::get('/pdf', [DocumentDownloadController::class, 'download']);
        });

        // --------- FLOOR WISE SALES ---------
        Route::get('/floor-wise-sales', [FloorWiseSalesController::class, 'index']);
        Route::get('/floor-wise-sales/locations', [FloorWiseSalesController::class, 'locations']);

        // --------- ATTENDANCE ---------
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::get('/attendance/locations', [AttendanceController::class, 'locations']);

        // --------- DAILY COLLECTION ---------
        Route::get('/daily-collection', [DailyCollectionController::class, 'index']);

        // --------- GATE ENTRY ---------
        Route::prefix('gateEntry')->group(function () {
            Route::get('/datatable', [GateEntryController::class, 'getDataTable']);
            Route::get('/grn/{id}', [GateEntryController::class, 'grnDetails'])->whereNumber('id');
            Route::get('/details/{id}', [GateEntryController::class, 'details'])->whereNumber('id');
            Route::get('/details/{id}/pdf', [GateEntryController::class, 'downloadPdf'])->whereNumber('id');
        });

        // --------- GRN ---------
        Route::prefix('grn')->group(function () {
            Route::get('/datatable', [GRNController::class, 'datatable']);
            Route::get('/{id}', [GRNController::class, 'show']);
            Route::get('/{id}/pdf', [GRNController::class, 'downloadPdf']);
        });

        // --------- LOCATIONS ---------
        Route::prefix('locations')->group(function () {
            Route::get('/all', [LocationController::class, 'getAll']);
            Route::get('/datatable', [LocationController::class, 'getDataTable']);
            Route::get('/codes', [LocationController::class, 'getLocationCodes']);
            Route::get('/gst-rates', [LocationController::class, 'getGstRates']);
            Route::get('/color-badges', [LocationController::class, 'getColorBadges']);
            Route::get('/{id}', [LocationController::class, 'getLocationById']);
            Route::post('/save', [LocationController::class, 'saveLocation']);
            Route::delete('/{id}', [LocationController::class, 'deleteLocation']);
        });

    }); // End of auth:sanctum middleware