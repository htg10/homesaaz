<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UploadgateentrybillController;
use App\Http\Controllers\HomeStayController;
use App\Http\Controllers\PendingRentController;
use App\Http\Controllers\DailyCollectionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\FloorWiseSalesController;
use App\Http\Controllers\StudentDetailsController;
use App\Http\Controllers\BedOccupancyController;
use App\Http\Controllers\NewStudentController;
use App\Http\Controllers\ItemStockController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\GateEntryController;
use App\Http\Controllers\GRNController;
use App\Http\Controllers\LocationController;




Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
   Route::middleware('throttle:10,1')->group(function () {
       Route::post('/login/otp/send', [AuthController::class, 'sendOtp'])->name('login.otp.send');
       Route::post('/login/otp/verify', [AuthController::class, 'verifyOtp'])->name('login.otp.verify');
   });

Route::get('/forgot-password', [AuthController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');

Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');

Route::middleware(['auth', 'field.permissions'])->group(function () {
    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password', [AuthController::class, 'passwordForm'])->name('password.form');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.change');

   /* User Routes - Only for Admins */
    Route::middleware('admin')->group(function () {
        Route::get('/users',[UserController::class, 'list'])->name('users.list');
        Route::get('/users/datatable',[UserController::class, 'getDataTable'])->name('users.datatable');
        // ==============================
        // FIELD PERMISSION ROUTES FIRST
        // ==============================
        Route::get('/users/access-options',[UserController::class, 'accessOptions'])->name('users.access.options');
        Route::get('/users/permission-tables',[UserController::class, 'getPermissionTables'])->name('users.permission.tables');
        Route::get('/users/table-fields',[UserController::class, 'getTableFields'])->name('users.permission.fields');
        Route::get('/users/{id}/field-permissions',[UserController::class, 'getUserFieldPermissions']) ->whereNumber('id')->name('users.field.permissions');
        // ==============================
        // USER SAVE / DELETE
        // ==============================
        Route::post('/users/save',[UserController::class, 'saveUser'])->name('users.save');
        Route::delete('/delete/users/{id}',[UserController::class, 'deleteUser'])->whereNumber('id')->name('users.destroy');
        // ==============================
        // GENERIC ID ROUTE ALWAYS LAST
        // ==============================
        Route::get('/users/{id}',[UserController::class, 'getUser'])->whereNumber('id')->name('users.get');
    });
    // Stock Routes
    Route::get('/stock', [StockController::class, 'list'])->name('stock.list');
    Route::get('/stock/search', [StockController::class, 'search'])->name('stock.search');
    Route::get('/stock/export', [StockController::class, 'export'])->name('stock.export');
    Route::get('/stock/datatable', [StockController::class, 'getDataTable'])->name('stock.datatable');
    Route::get('/stock/stats', [StockController::class, 'getStats'])->name('stock.stats');
    Route::get('/stock/companies-by-section', [StockController::class, 'getCompaniesBySection'])->name('stock.companiesBySection');
    Route::get('/stock/sections-by-location', [StockController::class, 'getSectionsByLocation'])->name('stock.sectionsByLocation');
    Route::get('/stock/filter-options', [StockController::class, 'getFilterOptions'])->name('stock.filterOptions');
    Route::get('/stock/{id}', [StockController::class, 'show'])->whereNumber('id')->name('stock.show');
    Route::get('/stock/image-proxy/{filename}', [StockController::class, 'imageProxy'])->name('stock.imageProxy');
    Route::get('/stock/item-locations', [StockController::class, 'itemLocations'])->name('stock.itemLocations');
    Route::get('/stock/supplier-locations', [StockController::class, 'supplierLocations'])->name('stock.supplierLocations');

 
    // UPLOAD GATE ENTRY BILL Routes
    Route::get('/uploadgateentrybill', [UploadgateentrybillController::class, 'list'])->name('uploadgateentrybill.list');
    Route::get('/uploadgateentrybill/datatable', [UploadgateentrybillController::class, 'datatable'])->name('uploadgateentrybill.datatable');
    Route::get('/uploadgateentrybill/form/{id?}', [UploadgateentrybillController::class, 'form'])->name('uploadgateentrybill.form');
    Route::post('/uploadgateentrybill/save', [UploadgateentrybillController::class, 'save'])->name('uploadgateentrybill.save');
    Route::delete('/uploadgateentrybill/{id}', [UploadgateentrybillController::class, 'delete'])->name('uploadgateentrybill.delete');
    Route::post('/uploadgateentrybill/{id}/updateStatus', [UploadgateentrybillController::class, 'updateStatus'])->name('uploadgateentrybill.updateStatus')->where('id', '[0-9]+');
    Route::post('/uploadgateentrybill/{id}/approve', [UploadgateentrybillController::class, 'approve'])->name('uploadgateentrybill.approve')->where('id', '[0-9]+');
    Route::post('/uploadgateentrybill/{id}/reject', [UploadgateentrybillController::class, 'reject'])->name('uploadgateentrybill.reject')->where('id', '[0-9]+');
    

    // Floor Wise Sales Routes
    Route::get('/floorWiseSales', [FloorWiseSalesController::class, 'list'])->name('floorWiseSales.list');
    Route::get('/floorWiseSales/data', [FloorWiseSalesController::class, 'getData'])->name('floorWiseSales.data');

    // Attendance Routes
    Route::get('/attendance', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/data', [AttendanceController::class, 'getData'])->name('attendance.data');

    // Daily Collection Routes
    Route::get('/dailyCollection', [DailyCollectionController::class, 'list'])->name('dailyCollection.list');
    Route::get('/dailyCollection/data', [DailyCollectionController::class, 'getData'])->name('dailyCollection.data');

    // Home Stay Routes
    Route::get('/homeStay', [HomeStayController::class, 'homeStay'])->name('homeStay.homeStay');
    Route::get('/pendingRent', [PendingRentController::class, 'list'])->name('pendingRent.list');
    Route::get('/api/pendingRent/datatable', [PendingRentController::class, 'getDataTable'])->name('pendingRent.datatable');
    Route::get('/studentDetails', [StudentDetailsController::class, 'list'])->name('studentDetails.list');
    Route::get('/api/studentDetails/datatable', [StudentDetailsController::class, 'getDataTable'])->name('studentDetails.datatable');
    Route::get('/bedOccupancy', [BedOccupancyController::class, 'list'])->name('bedOccupancy.list');
    Route::get('/api/bedOccupancy/datatable', [BedOccupancyController::class, 'getDataTable'])->name('bedOccupancy.datatable');
    Route::get('/newStudent', [NewStudentController::class, 'list'])->name('newStudent.list');
    Route::get('/api/newStudent/datatable', [NewStudentController::class, 'getDataTable'])->name('newStudent.datatable');

    // Item Stock Routes
    Route::get('/itemstock', [ItemStockController::class, 'list'])->name('itemstock.list');
    Route::get('/itemstock/permissions', [ItemStockController::class, 'getPermissions'])->name('itemstock.getPermissions');
    Route::post('/itemstock/search', [ItemStockController::class, 'searchItem'])->name('itemstock.search');
    Route::post('/itemstock/upload-image', [ItemStockController::class, 'uploadImage'])->name('itemstock.uploadImage');
    Route::post('/itemstock/update-mrp', [ItemStockController::class, 'updateMrp'])->name('itemstock.updateMrp');
    Route::post('/itemstock/update-discount', [ItemStockController::class, 'updateDiscount'])->name('itemstock.updateDiscount');
    Route::get('/itemstock/download-pdf', [ItemStockController::class, 'downloadPdf'])->name('itemstock.downloadPdf');
    
    // Document Download Routes
    // Document Download v2 — live from each Location's own database (no DocumentPrint table).
    Route::get('/documentdownload', [DocumentDownloadController::class, 'list'])->name('documentDownload.list');
    Route::get('/documentdownload/data', [DocumentDownloadController::class, 'getListData'])->name('documentDownload.getListData');
    Route::get('/documentdownload/live', [DocumentDownloadController::class, 'liveDetail'])->name('documentDownload.live');
    Route::get('/documentdownload/pdf', [DocumentDownloadController::class, 'downloadPdf'])->name('documentDownload.pdf');

    // Gate Entry Routes
    Route::get('/gateEntry', [GateEntryController::class, 'list'])->name('gateEntry.list');
    Route::get('/gateEntry/datatable', [GateEntryController::class, 'getDataTable'])->name('gateEntry.datatable');
    Route::get('/gateEntry/grn/{id}', [GateEntryController::class, 'grnDetails'])->whereNumber('id')->name('gateEntry.grnDetails');
    Route::get('/gateEntry/details/{id}', [GateEntryController::class, 'detailsPage'])->whereNumber('id')->name('gateEntry.details');
    Route::get('/gateEntry/details/{id}/pdf', [GateEntryController::class, 'downloadPdf'])->whereNumber('id')->name('gateEntry.pdf');
    
    
    // GRN Routes
    Route::get('/grn', [GRNController::class, 'list'])->name('grn.list');
    Route::get('/grn/datatable', [GRNController::class, 'datatable'])->name('grn.datatable');
    Route::get('/grn/{id}/view', [GRNController::class, 'detailPage'])->name('grn.detail');
    Route::get('/grn/{id}', [GRNController::class, 'show'])->name('grn.show');
    Route::get('/grn/{id}/pdf', [GRNController::class, 'downloadPdf'])->name('grn.pdf');

    // Location Routes
    Route::group(['prefix' => 'locations', 'as' => 'locations.'], function () {
    Route::get('/', [LocationController::class, 'list'])->name('list');
    Route::get('/api/all', [LocationController::class, 'getAll'])->name('api.all');
    Route::get('/api/datatable', [LocationController::class, 'getDataTable'])->name('api.datatable');
    Route::get('/api/show/{id}', [LocationController::class, 'getLocationById'])->name('api.show');
    Route::post('/api/save', [LocationController::class, 'saveLocation'])->name('api.save');
    Route::delete('/api/delete/{id}', [LocationController::class, 'deleteLocation'])->name('api.delete');
    Route::get('/api/codes', [LocationController::class, 'getLocationCodes'])->name('api.codes');
    Route::get('/api/gst-rates', [LocationController::class, 'getGstRates'])->name('api.gst-rates');
    Route::get('/api/color-badges', [LocationController::class, 'getColorBadges'])->name('api.color-badges');
});


});