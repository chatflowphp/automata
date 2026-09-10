<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\Context\ContextInterface;
use Automata\State\ResumableStateInterface;

final class ResumableRecordingState extends RecordingState implements ResumableStateInterface
{
    public int $resumeCount = 0;

    public function onResume(ContextInterface $context): void
    {
        $this->resumeCount++;
        $context->set('resumed', true);
    }
}
