<?php

declare(strict_types=1);

use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use StanislasPoisson\DevTools\Rector;

return Rector::configure(__DIR__, php: '8.2', paths: ['src', 'tests', 'database'])
    // The properties of the entities are the mapping of the tables, and their names carry a meaning:
    // `replacedBy` must not become `city` just because its type is City.
    ->withSkip([RenamePropertyToMatchTypeRector::class => [__DIR__ . '/src/Symfony/Entity']]);
