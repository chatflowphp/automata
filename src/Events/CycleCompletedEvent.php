<?php

declare(strict_types=1);

namespace Automata\Events;

use Automata\Contracts\EventInterface;
use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;

/**
 * Emitted by the orchestrator after a cycle fully completes.
 *
 * Carries both the original request and the produced response for observability purposes.
 * By the time listeners receive this event, all commands, transitions, and domain events from
 * the cycle have already been processed.
 */
final class CycleCompletedEvent implements EventInterface
{
    public const NAME = 'automata.cycle.completed';

    public function __construct(
        private readonly CycleRequest $request,
        private readonly CycleResponse $response
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{commands_count:int, events_count:int}
     */
    public function getPayload(): array
    {
        return [
            'commands_count' => count($this->response->getCommands()),
            'events_count' => count($this->response->getEvents()),
        ];
    }

    public function getRequest(): CycleRequest
    {
        return $this->request;
    }

    public function getResponse(): CycleResponse
    {
        return $this->response;
    }
}
