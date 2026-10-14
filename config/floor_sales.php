<?php

/*
|--------------------------------------------------------------------------
| Floor Wise Sales
|--------------------------------------------------------------------------
| Locations are DYNAMIC: every row of the Location table that has a
| "Sales Database" (Location.DatabaseName) appears in the dropdown.
| Set it from the Locations page - no code change needed for new locations.
*/

return [
    // Seconds to cache a result (today's data is cached shorter)
    'cache_seconds' => 600,
    'cache_seconds_today' => 120,

    // Max days in one search (the query is heavy)
    'max_days' => 92,
];