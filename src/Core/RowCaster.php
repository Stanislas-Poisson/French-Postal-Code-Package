<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

/**
 * Gives the values of a row the type their column has, so that every database receives numbers as numbers.
 */
final class RowCaster
{
    /**
     * @param array<string, string|null> $row
     * @param array<string, string>      $types the Table Schema type of each column
     *
     * @return array<string, float|int|string|null>
     */
    public function cast(array $row, array $types): array
    {
        $cast = [];

        foreach ($row as $column => $value) {
            $cast[$column] = match (null === $value ? 'null' : ($types[$column] ?? 'string')) {
                'null'    => null,
                'integer' => (int) $value,
                'number'  => (float) $value,
                default   => $value,
            };
        }

        return $cast;
    }
}
