<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Support;

/**
 * Checks a row against a Table Schema: required fields, types, patterns, allowed values and bounds.
 */
final class TableSchemaValidator
{
    /**
     * @param array{fields: list<array{name: string, type: string, constraints?: array<string, mixed>}>} $schema
     * @param array<string, string|null>                                                                 $row
     *
     * @return list<string> the violations, empty when the row is valid
     */
    public static function violations(array $schema, array $row): array
    {
        $violations = [];

        foreach ($schema['fields'] as $field) {
            $value       = $row[$field['name']]  ?? null;
            $constraints = $field['constraints'] ?? [];

            if (null === $value) {
                if (true === ($constraints['required'] ?? false)) {
                    $violations[] = $field['name'] . ' is required';
                }

                continue;
            }

            foreach (self::check($field['name'], $field['type'], $value, $constraints) as $violation) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    /**
     * @param array<string, mixed> $constraints
     *
     * @return list<string>
     */
    private static function check(string $name, string $type, string $value, array $constraints): array
    {
        $violations = [];

        $typeOk = match ($type) {
            'integer' => 1 === preg_match('/^-?\d+$/', $value),
            'number'  => is_numeric($value),
            'date'    => 1 === preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && false !== strtotime($value),
            default   => true,
        };

        if (! $typeOk) {
            return [$name . ' is not a ' . $type . ': ' . $value];
        }

        if (isset($constraints['pattern']) && is_string($constraints['pattern']) && 1 !== preg_match('/' . $constraints['pattern'] . '/', $value)) {
            $violations[] = $name . ' does not match its pattern: ' . $value;
        }

        if (isset($constraints['enum']) && is_array($constraints['enum']) && ! in_array($value, $constraints['enum'], true)) {
            $violations[] = $name . ' is not an allowed value: ' . $value;
        }

        if (is_numeric($value) && isset($constraints['minimum']) && is_numeric($constraints['minimum']) && (float) $value < (float) $constraints['minimum']) {
            $violations[] = $name . ' is below its minimum: ' . $value;
        }

        if (is_numeric($value) && isset($constraints['maximum']) && is_numeric($constraints['maximum']) && (float) $value > (float) $constraints['maximum']) {
            $violations[] = $name . ' is above its maximum: ' . $value;
        }

        return $violations;
    }
}
