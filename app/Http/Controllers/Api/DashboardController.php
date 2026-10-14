<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\UserAccessService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard tiles - only the modules this user is allowed to open
     * (admin gets all). Tile list lives in config/user_access.php.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $tiles = UserAccessService::dashboardTiles($user);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data fetched successfully',
            'data' => $tiles,
            'is_admin' => UserAccessService::isAdmin($user),
        ], 200);
    }
}
