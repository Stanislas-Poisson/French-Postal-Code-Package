<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;

// The sets of zairakai/laravel-dev-tools for PHP 8.2, without the Laravel ones.
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/database'])
    ->withCache(__DIR__ . '/build/rector')
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
    )
    // The properties of the entities are the mapping of the tables, and their names carry a meaning:
    // `replacedBy` must not become `city` just because its type is City.
    ->withSkip([RenamePropertyToMatchTypeRector::class => [__DIR__ . '/src/Symfony/Entity']])
    ->withParallel();
