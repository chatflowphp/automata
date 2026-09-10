<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown when a value written to context or state data is not snapshot-safe.
 */
final class InvalidStateValueException extends AutomataException {}
