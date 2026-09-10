<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown when a snapshot cannot be validated, serialized, migrated, or restored.
 */
final class SnapshotHydrationException extends AutomataException {}
