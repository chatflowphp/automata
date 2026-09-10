<?php

declare(strict_types=1);

namespace Automata\Messaging;

/**
 * Marker for messages that request an action. Commands are dispatched once, in order,
 * after the state machine has applied any transition they carry.
 */
interface CommandInterface {}
