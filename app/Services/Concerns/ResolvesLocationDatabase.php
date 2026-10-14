<?php

namespace App\Services\Concerns;

use App\Services\UserAccessService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Shared by any service that needs to run a query on ONE location's own
 * database (Location.DatabaseName) rather than the shared app database.
 *
 * This is the exact pattern already proven in
 * App\Services\FloorWiseSalesService — pulled out here so other modules
 * (e.g. LiveDocumentPrintService) can reuse it without duplicating it.
 */
trait ResolvesLocationDatabase
{
    /**
     * Every Location row with a DatabaseName, limited to the locations
     * this user may see.
     *
     * @return array<int, array{id:int, name:string, database:string, color:string}>
     */
    public static function locationsWithDatabase(): array
    {
        $all = (function () {
            if (!Schema::hasColumn('Location', 'DatabaseName')) {
                Log::warning(static::class.': Location.DatabaseName column is missing');
                return [];
            }

            return DB::table('Location')
                ->select('LocationID', 'LocationName', 'DatabaseName', 'Color')
                ->whereNotNull('DatabaseName')
                ->where('DatabaseName', '<>', '')
                ->orderBy('LocationName')
                ->get()
                ->map(fn ($l) => [
                    'id' => (int) $l->LocationID,
                    'name' => trim((string) $l->LocationName),
                    'database' => strtoupper(trim((string) $l->DatabaseName)),
                    'color' => trim((string) ($l->Color ?? '')) ?: '#9CA3AF',
                ])
                ->filter(fn ($l) => preg_match('/^[A-Z0-9_]+$/', $l['database']))
                ->values()
                ->all();
        })();

        $allowed = UserAccessService::allowedLocationIds();
        if ($allowed === null) {
            return $all;
        }

        return array_values(array_filter($all, fn ($l) => in_array($l['id'], $allowed, true)));
    }

    public static function databaseForLocation(int $locationId): ?string
    {
        foreach (self::locationsWithDatabase() as $loc) {
            if ($loc['id'] === $locationId) {
                return $loc['database'];
            }
        }
        return null;
    }

    /** Does the database exist on the server? (cached 10 min) */
    protected static function locationDatabaseExists(string $database): bool
    {
        return Cache::remember('location_db.exists.'.$database, 600, function () use ($database) {
            $row = DB::selectOne('SELECT DB_ID(?) AS DbId', [$database]);
            return !empty($row->DbId);
        });
    }

    /**
     * Connection name for another database on the same server (copy of the
     * default connection with a different database name). Registers the
     * connection config on first use, then just returns the name.
     */
    protected static function connectionForLocationDatabase(string $database): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new \InvalidArgumentException('Invalid database name.');
        }

        $name = 'loc_db_'.strtolower($database);

        if (!config("database.connections.$name")) {
            $base = config('database.connections.'.config('database.default'));
            $base['database'] = $database;
            config(["database.connections.$name" => $base]);
        }

        return $name;
    }

    /**
     * Resolve a locationId straight to a usable DB::connection() instance.
     * Throws a clear, user-facing message if the location has no database
     * configured or the database isn't actually on the server.
     */
    protected static function connectionForLocation(int $locationId): \Illuminate\Database\Connection
    {
        $database = self::databaseForLocation($locationId);
        if (!$database) {
            throw new \RuntimeException('No Sales Database is set for this location. Please set it on the Locations page.');
        }
        if (!self::locationDatabaseExists($database)) {
            throw new \RuntimeException("Database {$database} was not found on the server. Please check the Locations page.");
        }

        return DB::connection(self::connectionForLocationDatabase($database));
    }
}