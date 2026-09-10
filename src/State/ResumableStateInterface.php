<?php

declare(strict_types=1);

namespace Automata\State;

use Automata\Context\ContextInterface;

/**
 * A state that wants a callback when it becomes current through restore().
 *
 * restore() never calls onEnter(), because the snapshot already reflects a state that was entered.
 * Implement this when the state needs to re-arm something in memory, such as a timer or a cache.
 */
interface ResumableStateInterface extends StateInterface
{
    public function onResume(ContextInterface $context): void;
}
