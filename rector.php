<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

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
    ->withParallel();
