<?php

namespace App\Services;

use App\Models\UserFieldPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FieldPermissionService
{
    public static function enabled(): bool
    {
        return (bool) config('field_permissions.enabled', false);
    }

    public static function isAdmin($user): bool
    {
        return $user && (int) ($user->user_role ?? 0) === 1;
    }

    public static function userId($user)
    {
        return $user?->ID ?? $user?->getKey();
    }

    public static function allowedFields($user, string $table): array
    {
        if (!$user) {
            return [];
        }

        if (self::isAdmin($user) || !self::enabled()) {
            return self::allFields($table);
        }

        $userId = self::userId($user);
        if (!$userId) {
            return [];
        }

        return UserFieldPermission::query()
            ->where('user_id', $userId)
            ->whereRaw('LOWER(table_name) = LOWER(?)', [$table])
            ->where('can_view', 1)
            ->pluck('field_name')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }

    public static function allFields(string $table): array
    {
        try {
            if (!Schema::hasTable($table)) {
                return config("field_permissions.virtual_fields.$table", []);
            }

            return Schema::getColumnListing($table);
        } catch (\Throwable $e) {
            return config("field_permissions.virtual_fields.$table", []);
        }
    }

    public static function canView($user, string $table, string $field): bool
    {
        if (self::isAdmin($user) || !self::enabled()) {
            return true;
        }

        $needle = strtolower($field);
        foreach (self::allowedFields($user, $table) as $allowed) {
            if (strtolower($allowed) === $needle) {
                return true;
            }
        }

        return false;
    }

    public static function tablesForRequest(Request $request): array
    {
        if (!self::enabled()) {
            return [];
        }

        $path = strtolower(trim($request->path(), '/'));
        $rules = config('field_permissions.routes', []);

        foreach ($rules as $prefix => $tables) {
            $prefix = strtolower(trim($prefix, '/'));
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return (array) $tables;
            }
        }

        return [];
    }

    public static function permissionContext($user, array $tables): array
    {
        $isAdmin = self::isAdmin($user);
        $allowedByTable = [];
        $allByTable = [];
        $allowedKeys = [];
        $allKeys = [];

        foreach ($tables as $table) {
            $all = self::allFields($table);
            $allowed = $isAdmin ? $all : self::allowedFields($user, $table);

            $allByTable[$table] = array_values($all);
            $allowedByTable[$table] = array_values($allowed);

            foreach ($all as $field) {
                foreach (self::fieldAliases($table, $field) as $alias) {
                    $allKeys[strtolower($alias)] = true;
                }
            }

            foreach ($allowed as $field) {
                foreach (self::fieldAliases($table, $field) as $alias) {
                    $allowedKeys[strtolower($alias)] = true;
                }
            }
        }

        return [
            'is_admin' => $isAdmin,
            'tables' => array_values($tables),
            'allowed_by_table' => $allowedByTable,
            'all_by_table' => $allByTable,
            'allowed_keys' => array_keys($allowedKeys),
            'all_keys' => array_keys($allKeys),
        ];
    }

    public static function filterPayload($payload, array $context)
    {
        if (($context['is_admin'] ?? false) || empty($context['tables'])) {
            return $payload;
        }

        $allowed = array_fill_keys(array_map('strtolower', $context['allowed_keys'] ?? []), true);
        $known = array_fill_keys(array_map('strtolower', $context['all_keys'] ?? []), true);
        $always = array_fill_keys(array_map('strtolower', config('field_permissions.always_visible_keys', [])), true);

        return self::filterValue($payload, $allowed, $known, $always, true);
    }

    private static function filterValue($value, array $allowed, array $known, array $always, bool $topLevel = false)
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn ($item) => self::filterValue($item, $allowed, $known, $always, false),
                $value
            );
        }

        $out = [];

        foreach ($value as $key => $item) {
            $keyLower = strtolower((string) $key);

            // Only explicit envelope/meta keys are always preserved.
            // Known permission fields are filtered even when they appear at the top level.
            if (isset($always[$keyLower])) {
                $out[$key] = self::filterValue($item, $allowed, $known, $always, false);
                continue;
            }

            // If this key represents a known module field, preserve it only when allowed.
            if (isset($known[$keyLower])) {
                if (isset($allowed[$keyLower])) {
                    $out[$key] = self::filterValue($item, $allowed, $known, $always, false);
                }
                continue;
            }

            // Unknown presentation/derived keys are preserved for compatibility.
            $out[$key] = self::filterValue($item, $allowed, $known, $always, false);
        }

        return $out;
    }

    public static function fieldAliases(string $table, string $field): array
    {
        $aliases = [$field];
        $tableAliases = config("field_permissions.aliases.$table", []);
        $configured = [];

        // SQL Server field casing is not always consistent (e.g. StKQty/StkQty).
        // Resolve configured aliases case-insensitively.
        foreach ((array) $tableAliases as $configuredField => $values) {
            if (strcasecmp((string) $configuredField, (string) $field) === 0) {
                $configured = is_array($values) ? $values : [$values];
                break;
            }
        }

        return array_values(array_unique(array_merge($aliases, $configured)));
    }

    public static function permissionTables(): array
    {
        try {
            $tables = collect(DB::select("\n                SELECT TABLE_NAME\n                FROM INFORMATION_SCHEMA.TABLES\n                WHERE TABLE_TYPE = 'BASE TABLE'\n                ORDER BY TABLE_NAME\n            "))->pluck('TABLE_NAME')->map(fn ($v) => (string) $v)->all();
        } catch (\Throwable $e) {
            $tables = [];
        }

        $virtual = array_keys(config('field_permissions.virtual_fields', []));

        return array_values(array_unique(array_merge($tables, $virtual)));
    }
}
