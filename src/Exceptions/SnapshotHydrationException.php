<?php

declare(strict_types=1);

namespace Automata\Exceptions;

/**
 * Thrown when snapshot data cannot be validated, serialized, or restored safely.
 */
final class SnapshotHydrationException extends AutomataException
{
}
