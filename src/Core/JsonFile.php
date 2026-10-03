<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

/**
 * Reads a file of the data, without silencing the errors of PHP.
 */
final class JsonFile
{
    /**
     * @throws DatasetException when the file is missing
     */
    public static function contents(string $path): string
    {
        $contents = is_file($path) ? file_get_contents($path) : false;

        return false === $contents ? throw DatasetException::missingFile($path) : $contents;
    }
}
