<?php

declare(strict_types=1);

return [
    // Prefix of the tables of the package. Many applications already own a `cities` or a `regions` table:
    // with the default, the package creates `french_regions`, `french_departments`, `french_communes`,
    // `french_cities` and `french_commune_successions`. Set it to an empty string to use the bare names.
    'table_prefix' => env('FRENCH_POSTAL_CODE_TABLE_PREFIX', 'french_'),

    // Database connection of the tables. `null` is the default connection of the application.
    'connection' => env('FRENCH_POSTAL_CODE_CONNECTION'),
];
