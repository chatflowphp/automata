<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown when an operation is called while the state machine is in the wrong lifecycle phase,
 * for example tick() before start() or restore() after start().
 */
class IllegalStateException extends AutomataException {}
