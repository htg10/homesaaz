<?php

namespace App\Services;

use App\Models\UserAccessPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Central place for the new user access rules:
 *   - which dashboard modules a user can open
 *   - which fields inside a module a user can see (e.g. Item Stock)
 *   - which locations (branches) a user can see data for
 *
 * Admin (user_role = 1) always has full access.
 */
class UserAccessService
{
    /** Per-request cache: userId => ['modules'=>[], 'fields'=>[], 'locations'=>[]] */
    private static array $loaded = [];

    /* ------------------------------------------------------------------
     | Basics
     * ------------------------------------------------------------------ */

    public static function currentUser()
    {
        return auth()->user() ?: auth('sanctum')->user();
    }

    public static function isAdmin($user = null): bool
    {
        $user = $user ?: self::currentUser();
        return $user && (int) ($user->user_role ?? 0) === 1;
    }

    public static function userId($user)
    {
        return $user?->ID ?? $user?->getKey();
    }

    public static function modulesConfig(): array
    {
        return config('user_access.modules', []);
    }

    public static function moduleFields(string $module): array
    {
        return config("user_access.modules.$module.fields", []);
    }

    private static function load($user): array
    {
        $id = self::userId($user);
        if (!$id) {
            return ['modules' => [], 'fields' => [], 'locations' => []];
        }

        if (isset(self::$loaded[$id])) {
            return self::$loaded[$id];
        }

        $data = ['modules' => [], 'fields' => [], 'locations' => []];

        try {
            if (Schema::hasTable('user_access_permissions')) {
                $rows = UserAccessPermission::where('user_id', $id)->get(['perm_type', 'module', 'perm_key']);

                foreach ($rows as $row) {
                    if ($row->perm_type === 'module') {
                        $data['modules'][] = $row->module;
                    } elseif ($row->perm_type === 'field') {
                        $data['fields'][$row->module][] = $row->perm_key;
                    } elseif ($row->perm_type === 'location') {
                        $data['locations'][] = (int) $row->perm_key;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('UserAccessService load failed', ['error' => $e->getMessage()]);
        }

        $data['modules'] = array_values(array_unique($data['modules']));
        $data['locations'] = array_values(array_unique(array_filter($data['locations'])));

        return self::$loaded[$id] = $data;
    }

    /* ------------------------------------------------------------------
     | Modules
     * ------------------------------------------------------------------ */

    public static function allowedModules($user = null): array
    {
        $user = $user ?: self::currentUser();
        if (!$user) {
            return [];
        }
        if (self::isAdmin($user)) {
            return array_keys(self::modulesConfig());
        }
        return self::load($user)['modules'];
    }

    public static function canModule(string $module, $user = null): bool
    {
        return in_array($module, self::allowedModules($user), true);
    }

    /**
     * Dashboard tiles the user may see (web + API share this).
     */
    public static function dashboardTiles($user = null): array
    {
        $allowed = self::allowedModules($user);
        $tiles = [];

        foreach (self::modulesConfig() as $slug => $module) {
            if (!in_array($slug, $allowed, true)) {
                continue;
            }

            $route = null;
            if (!empty($module['route']) && \Illuminate\Support\Facades\Route::has($module['route'])) {
                $route = route($module['route']);
            }

            $tiles[] = [
                'label' => $module['label'],
                'slug' => $slug,
                'image' => $module['image'] ?? null,
                'icon' => $module['icon'] ?? null,
                'route' => $route,
            ];
        }

        return $tiles;
    }

    /**
     * Which module does this request path belong to? null = not module-specific.
     */
    public static function moduleForPath(string $path): ?string
    {
        $path = strtolower(trim($path, '/'));

        foreach (config('user_access.shared_paths', []) as $shared) {
            $shared = strtolower(trim($shared, '/'));
            if ($path === $shared || str_starts_with($path, $shared . '/')) {
                return null;
            }
        }

        foreach (self::modulesConfig() as $slug => $module) {
            foreach ($module['paths'] ?? [] as $prefix) {
                $prefix = strtolower(trim($prefix, '/'));
                if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
                    return $slug;
                }
            }
        }

        return null;
    }

    /* ------------------------------------------------------------------
     | Fields inside a module
     * ------------------------------------------------------------------ */

    public static function canField(string $module, string $field, $user = null): bool
    {
        $user = $user ?: self::currentUser();
        if (!$user) {
            return false;
        }
        if (self::isAdmin($user)) {
            return true;
        }
        return in_array($field, self::load($user)['fields'][$module] ?? [], true);
    }

    /** ['supplier_name' => true, ...] for every configured field of the module. */
    public static function fieldMap(string $module, $user = null): array
    {
        $map = [];
        foreach (array_keys(self::moduleFields($module)) as $key) {
            $map[$key] = self::canField($module, $key, $user);
        }
        return $map;
    }

    /* ------------------------------------------------------------------
     | Locations
     * ------------------------------------------------------------------ */

    /** Per-request copy of the Location table (no long cache -> colours/names are always live). */
    private static ?array $locationsCache = null;

    /**
     * All locations straight from the Location table (name, code, colour).
     * Read once per request, so a change on the Locations page shows immediately.
     */
    public static function allLocations(): array
    {
        if (self::$locationsCache !== null) {
            return self::$locationsCache;
        }

        return self::$locationsCache = (function () {
            return DB::table('Location')
                ->select('LocationID', 'LocationName', 'Locationcode', 'Color')
                ->orderBy('LocationName')
                ->get()
                ->map(fn ($l) => [
                    'id' => (int) $l->LocationID,
                    'name' => trim((string) $l->LocationName),
                    'code' => trim((string) $l->Locationcode),
                    'color' => trim((string) ($l->Color ?? '')) ?: '#9CA3AF',
                ])
                ->values()
                ->all();
        })();
    }

    /** null = all locations (admin), otherwise array of LocationIDs. */
    public static function allowedLocationIds($user = null): ?array
    {
        $user = $user ?: self::currentUser();
        if (!$user) {
            return [];
        }
        if (self::isAdmin($user)) {
            return null;
        }
        return self::load($user)['locations'];
    }

    public static function canLocation($locationId, $user = null): bool
    {
        $ids = self::allowedLocationIds($user);
        return $ids === null || in_array((int) $locationId, $ids, true);
    }

    /**
     * Location to use when a request did not send one: the preferred ID if
     * allowed, otherwise the user's first allowed location (0 = none).
     */
    public static function defaultLocationId(int $preferred, $user = null): int
    {
        $ids = self::allowedLocationIds($user);
        if ($ids === null || in_array($preferred, $ids, true)) {
            return $preferred;
        }
        return (int) ($ids[0] ?? 0);
    }

    /** null = all, otherwise upper-case Locationcodes the user may see. */
    public static function allowedLocationCodes($user = null): ?array
    {
        $ids = self::allowedLocationIds($user);
        if ($ids === null) {
            return null;
        }

        $codes = [];
        foreach (self::allLocations() as $loc) {
            if (in_array($loc['id'], $ids, true) && $loc['code'] !== '') {
                $codes[] = strtoupper($loc['code']);
            }
        }
        return array_values(array_unique($codes));
    }

    /**
     * Does a location code/name like "LJP" or "LJP-GF" belong to an allowed location?
     * Matches the full value or the part before "-".
     */
    public static function canLocationCode(?string $value, $user = null): bool
    {
        $codes = self::allowedLocationCodes($user);
        if ($codes === null) {
            return true;
        }

        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return false;
        }

        $base = trim(explode('-', $value)[0]);
        return in_array($value, $codes, true) || in_array($base, $codes, true);
    }

    /** Filter a collection/array of rows having LocationID. */
    public static function filterByLocationId($rows, string $key = 'LocationID', $user = null)
    {
        $ids = self::allowedLocationIds($user);
        if ($ids === null) {
            return $rows;
        }

        $filter = fn ($row) => in_array((int) (is_array($row) ? ($row[$key] ?? 0) : ($row->{$key} ?? 0)), $ids, true);

        if ($rows instanceof \Illuminate\Support\Collection) {
            return $rows->filter($filter)->values();
        }

        return array_values(array_filter((array) $rows, $filter));
    }

    /* ------------------------------------------------------------------
     | Read / save for the User form
     * ------------------------------------------------------------------ */

    public static function accessForUser($user): array
    {
        $data = self::load($user);

        return [
            'modules' => $data['modules'],
            'fields' => (object) $data['fields'],
            'locations' => $data['locations'],
        ];
    }

    /** Options for the User form (modules with fields + locations). */
    public static function formOptions(): array
    {
        $modules = [];
        foreach (self::modulesConfig() as $slug => $module) {
            $fields = [];
            foreach ($module['fields'] ?? [] as $key => $label) {
                $fields[] = ['key' => $key, 'label' => $label];
            }

            $modules[] = [
                'slug' => $slug,
                'label' => $module['label'],
                'image' => !empty($module['image']) ? asset('assets/images/dashboard/' . $module['image']) : null,
                'fields' => $fields,
            ];
        }

        return [
            'modules' => $modules,
            'locations' => self::allLocations(),
        ];
    }

    /**
     * Save modules / fields / locations sent by the User form.
     * Expected input:
     *   access_modules[]            = ['item-stock', 'gate-entry']
     *   access_fields[item-stock][] = ['supplier_name', 'mrp']
     *   access_locations[]          = [8, 26]
     */
    public static function saveForUser($userId, Request $request): void
    {
        if (!$userId || !Schema::hasTable('user_access_permissions')) {
            return;
        }

        // Only touch permissions when the form actually sent the access section.
        if (!$request->has('access_submitted')) {
            return;
        }

        $config = self::modulesConfig();
        $validLocationIds = array_column(self::allLocations(), 'id');

        $modules = array_values(array_intersect(
            array_map('strval', (array) $request->input('access_modules', [])),
            array_keys($config)
        ));

        $rows = [];
        $now = now();

        foreach ($modules as $module) {
            $rows[] = ['user_id' => $userId, 'perm_type' => 'module', 'module' => $module, 'perm_key' => '*'];

            $allowedFields = array_keys($config[$module]['fields'] ?? []);
            $picked = (array) $request->input("access_fields.$module", []);
            foreach (array_intersect(array_map('strval', $picked), $allowedFields) as $field) {
                $rows[] = ['user_id' => $userId, 'perm_type' => 'field', 'module' => $module, 'perm_key' => $field];
            }
        }

        foreach ((array) $request->input('access_locations', []) as $locationId) {
            $locationId = (int) $locationId;
            if (in_array($locationId, $validLocationIds, true)) {
                $rows[] = ['user_id' => $userId, 'perm_type' => 'location', 'module' => '*', 'perm_key' => (string) $locationId];
            }
        }

        $rows = array_values(array_unique($rows, SORT_REGULAR));
        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        UserAccessPermission::where('user_id', $userId)->delete();
        foreach (array_chunk($rows, 100) as $chunk) {
            UserAccessPermission::insert($chunk);
        }

        unset(self::$loaded[$userId]);
    }

    public static function deleteForUser($userId): void
    {
        try {
            if (Schema::hasTable('user_access_permissions')) {
                UserAccessPermission::where('user_id', $userId)->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('UserAccessService delete failed', ['error' => $e->getMessage()]);
        }
    }
}