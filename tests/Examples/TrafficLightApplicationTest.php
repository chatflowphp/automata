<?php

declare(strict_types=1);

namespace Automata\Tests\Examples;

use Automata\Clock\FrozenClock;
use Automata\Snapshot\JsonSnapshotSerializer;
use AutomataExamples\TrafficLight\Application\TrafficLightApplication;
use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;
use AutomataExamples\TrafficLight\Output;
use PHPUnit\Framework\TestCase;

final class TrafficLightApplicationTest extends TestCase
{
    public function testLightsCycleAndResumeFromSnapshot(): void
    {
        $clock = FrozenClock::at('2026-09-10T12:00:00+00:00');
        $output = new Output();
        $application = new TrafficLightApplication($output, $clock);

        $application->start();
        $application->runTicks(1, 5);

        self::assertSame([
            '   tick 1 -> color=RED | total_ticks=1 | state=traffic_light.red',
            '[12:00:00] Light changed from RED to GREEN (context now: GREEN)',
            '   tick 2 -> color=GREEN | total_ticks=2 | state=traffic_light.green',
            '   tick 3 -> color=GREEN | total_ticks=3 | state=traffic_light.green',
            '[12:00:00] Light changed from GREEN to YELLOW (context now: YELLOW)',
            '   tick 4 -> color=YELLOW | total_ticks=4 | state=traffic_light.yellow',
            '[12:00:00] Light changed from YELLOW to RED (context now: RED)',
            '   tick 5 -> color=RED | total_ticks=5 | state=traffic_light.red',
        ], $output->all());

        $serializer = new JsonSnapshotSerializer();
        $payload = $serializer->serialize($application->getMachine()->snapshot());

        $restoredOutput = new Output();
        $restored = new TrafficLightApplication($restoredOutput, $clock);
        $restored->getMachine()->restore($serializer->deserialize($payload));

        self::assertSame(TrafficLightStatus::RED->value, $restored->getMachine()->getCurrentStateId());
        self::assertSame(5, $restored->getMachine()->getTickCount());

        $restored->runTicks(6, 7);

        self::assertSame([
            '   tick 6 -> color=RED | total_ticks=6 | state=traffic_light.red',
            '[12:00:00] Light changed from RED to GREEN (context now: GREEN)',
            '   tick 7 -> color=GREEN | total_ticks=7 | state=traffic_light.green',
        ], $restoredOutput->all());
    }

    public function testTransitionGraphIsDeclared(): void
    {
        $application = new TrafficLightApplication();

        self::assertSame([
            'traffic_light.red' => ['traffic_light.green'],
            'traffic_light.green' => ['traffic_light.yellow'],
            'traffic_light.yellow' => ['traffic_light.red'],
        ], $application->getTransitions()->edges());

        $application->start();

        self::assertSame(['traffic_light.green'], $application->getMachine()->getAllowedTransitions());
        self::assertFalse($application->getMachine()->canTransitionTo('traffic_light.yellow'));
    }
}
