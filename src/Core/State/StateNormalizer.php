<?php

declare(strict_types=1);

namespace Automata\Core\State;

use Automata\Exceptions\SnapshotHydrationException;
use BackedEnum;

/**
 * Normalizes state trees into snapshot-safe scalar/array structures.
 */
final class StateNormalizer
{
    /**
     * @param array<array-key, mixed> $state
     *
     * @return array<array-key, mixed>
     */
    public static function normalizeArray(array $state, string $path): array
    {
        $normalized = [];

        foreach ($state as $key => $value) {
            $normalized[$key] = self::normalizeValue($value, sprintf('%s.%s', $path, (string) $key));
        }

        return $normalized;
    }

    public static function normalizeValue(mixed $value, string $path): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            throw new SnapshotHydrationException(sprintf(
                'Invalid snapshot state at "%s": non-backed enums are not supported.',
                $path
            ));
        }

        if (is_array($value)) {
            return self::normalizeArray($value, $path);
        }

        throw new SnapshotHydrationException(sprintf(
            'Invalid snapshot state at "%s": value must be scalar, array, backed enum, or null.',
            $path
        ));
    }
}
