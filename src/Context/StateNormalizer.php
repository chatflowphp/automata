<?php

declare(strict_types=1);

namespace Automata\Context;

use Automata\Exception\InvalidStateValueException;
use BackedEnum;
use UnitEnum;

/**
 * Normalizes values into the snapshot-safe subset: null, bool, int, float, string, arrays of those.
 * Backed enums are replaced by their backing value.
 */
final class StateNormalizer
{
    /**
     * Normalizes a top-level state map. Keys must be strings that PHP does not coerce to integers.
     *
     * @param array<array-key, mixed> $state
     *
     * @return array<string, mixed>
     */
    public static function normalizeMap(array $state, string $path): array
    {
        $normalized = [];

        foreach ($state as $key => $value) {
            if (!\is_string($key)) {
                throw new InvalidStateValueException(\sprintf(
                    'Invalid state at "%s": keys must be non-numeric strings, got %s.',
                    $path,
                    var_export($key, true),
                ));
            }

            $normalized[$key] = self::normalizeValue($value, $path . '.' . $key);
        }

        return $normalized;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    public static function normalizeArray(array $values, string $path): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            $normalized[$key] = self::normalizeValue($value, $path . '.' . $key);
        }

        return $normalized;
    }

    public static function normalizeValue(mixed $value, string $path): mixed
    {
        if ($value === null || \is_bool($value) || \is_int($value) || \is_float($value) || \is_string($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            throw new InvalidStateValueException(\sprintf(
                'Invalid state at "%s": non-backed enums are not supported.',
                $path,
            ));
        }

        if (\is_array($value)) {
            return self::normalizeArray($value, $path);
        }

        throw new InvalidStateValueException(\sprintf(
            'Invalid state at "%s": value must be scalar, array, backed enum, or null, got %s.',
            $path,
            get_debug_type($value),
        ));
    }

    /**
     * Whether PHP would silently convert this array key to an integer.
     */
    public static function isNumericKey(string $key): bool
    {
        return (string) (int) $key === $key;
    }
}
