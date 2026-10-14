<?php

namespace App\Http\Middleware;

use App\Services\FieldPermissionService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplyFieldPermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tables = FieldPermissionService::tablesForRequest($request);
        $context = FieldPermissionService::permissionContext($user, $tables);

        View::share('fieldPermissionContext', $context);

        $response = $next($request);

        if ($response instanceof JsonResponse && !empty($tables) && !($context['is_admin'] ?? false)) {
            $original = $response->getData(true);
            $filtered = FieldPermissionService::filterPayload($original, $context);

            // Client receives the permission context so dynamic DataTables can build/hide columns.
            if (is_array($filtered)) {
                $filtered['_field_permissions'] = $context;
            }

            $response->setData($filtered);
        }

        return $response;
    }
}
