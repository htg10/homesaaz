<?php

namespace App\Http\Middleware;

use App\Services\UserAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks requests to a module (web + api) when the logged-in user
 * does not have that module ticked on the User form. Admin passes always.
 */
class CheckModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?: auth('sanctum')->user();

        if (!$user || UserAccessService::isAdmin($user)) {
            return $next($request);
        }

        $module = UserAccessService::moduleForPath($request->path());

        if ($module !== null && !UserAccessService::canModule($module, $user)) {
            return $this->deny($request, 'You do not have permission to access this module.');
        }

        // Any request asking for a specific location must be for an allowed location.
        $locationId = $request->input('locationID', $request->input('LocationID', $request->input('location_id')));
        if ($locationId !== null && $locationId !== '' && is_numeric($locationId)
            && !UserAccessService::canLocation((int) $locationId, $user)) {
            return $this->deny($request, 'You do not have permission to view this location.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return redirect()->route('dashboard')->with('error', $message);
    }
}
