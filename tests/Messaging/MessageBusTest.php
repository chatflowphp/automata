<?php

declare(strict_types=1);

namespace Automata\Tests\Messaging;

use Automata\Events\StateActivated;
use Automata\Events\TransitionApplied;
use Automata\Messaging\EventInterface;
use Automata\Messaging\MessageBus;
use Automata\Messaging\TransitionCommand;
use Automata\Messaging\TransitionCommandInterface;
use Automata\Tests\Support\TestCommand;
use Automata\Tests\Support\TestEvent;
use PHPUnit\Framework\TestCase;

final class MessageBusTest extends TestCase
{
    public function testHandlersRunInSubscriptionOrderForExactClass(): void
    {
        $bus = new MessageBus();
        $log = [];

        $bus->subscribe(TestEvent::class, static function (TestEvent $event) use (&$log): void {
            $log[] = 'first:' . $event->label;
        });
        $bus->subscribe(TestEvent::class, static function (TestEvent $event) use (&$log): void {
            $log[] = 'second:' . $event->label;
        });

        $bus->dispatch(new TestEvent('a'));

        self::assertSame(['first:a', 'second:a'], $log);
    }

    public function testDispatchReachesInterfaceSubscribersAfterExactClassSubscribers(): void
    {
        $bus = new MessageBus();
        $log = [];

        $bus->subscribe(EventInterface::class, static function (EventInterface $event) use (&$log): void {
            $log[] = 'any-event:' . $event::class;
        });
        $bus->subscribe(TestEvent::class, static function (TestEvent $event) use (&$log): void {
            $log[] = 'exact:' . $event->label;
        });
        $bus->subscribe(TransitionCommandInterface::class, static function (TransitionCommandInterface $command) use (&$log): void {
            $log[] = 'transition:' . $command->getTargetStateId();
        });

        $bus->dispatch(new TestEvent('x'));
        $bus->dispatch(new TransitionCommand('next'));
        $bus->dispatch(new TestCommand('ignored'));

        self::assertSame(['exact:x', 'any-event:' . TestEvent::class, 'transition:next'], $log);
    }

    public function testDispatchWithoutSubscribersIsSilent(): void
    {
        $bus = new MessageBus();

        $bus->dispatch(new StateActivated('a', null));
        $bus->dispatch(new TransitionApplied('a', 'b'));

        $this->addToAssertionCount(1);
    }

    public function testHandlerReceivesTheSameInstance(): void
    {
        $bus = new MessageBus();
        $received = null;

        $bus->subscribe(TestEvent::class, static function (TestEvent $event) use (&$received): void {
            $received = $event;
        });

        $event = new TestEvent('same');
        $bus->dispatch($event);

        self::assertSame($event, $received);
    }
}
