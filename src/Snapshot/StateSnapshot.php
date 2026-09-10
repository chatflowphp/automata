<?php

declare(strict_types=1);

namespace Automata\Snapshot;

use Automata\Context\StateNormalizer;
use Automata\Exception\InvalidStateValueException;
use Automata\Exception\SnapshotHydrationException;
use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

/**
 * Immutable, validated capture of a state machine: shared context, current state, per-state data,
 * tick counter, and creation time. Carries a schema version so persisted snapshots can be migrated.
 */
final class StateSnapshot implements JsonSerializable
{
    public const SCHEMA_VERSION = 2;

    /**
     * @param array<string, mixed> $contextState
     * @param array<string, array<string, mixed>> $stateData
     */
    private function __construct(
        public readonly array $contextState,
        public readonly ?string $currentStateId,
        public readonly array $stateData,
        public readonly int $tickCount,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    /**
     * @param array<array-key, mixed> $contextState
     * @param array<array-key, mixed> $stateData
     *
     * @throws SnapshotHydrationException
     */
    public static function create(
        array $contextState,
        ?string $currentStateId,
        array $stateData,
        int $tickCount,
        DateTimeImmutable $createdAt,
    ): self {
        if ($currentStateId === '') {
            throw new SnapshotHydrationException('Invalid snapshot: "currentStateId" must be a non-empty string or null.');
        }

        if ($tickCount < 0) {
            throw new SnapshotHydrationException('Invalid snapshot: "tickCount" must not be negative.');
        }

        try {
            return new self(
                StateNormalizer::normalizeMap($contextState, 'contextState'),
                $currentStateId,
                self::normalizeStateData($stateData),
                $tickCount,
                $createdAt,
            );
        } catch (InvalidStateValueException $exception) {
            throw new SnapshotHydrationException('Invalid snapshot: ' . $exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Builds a snapshot from its array form. The array must already be at SCHEMA_VERSION;
     * JsonSnapshotSerializer runs migrations before calling this.
     *
     * @param array<array-key, mixed> $data
     *
     * @throws SnapshotHydrationException
     */
    public static function fromArray(array $data): self
    {
        $version = $data['schemaVersion'] ?? null;

        if ($version !== self::SCHEMA_VERSION) {
            throw new SnapshotHydrationException(\sprintf(
                'Unsupported snapshot schema version %s, expected %d. Register migrations on the serializer.',
                \is_scalar($version) ? var_export($version, true) : get_debug_type($version),
                self::SCHEMA_VERSION,
            ));
        }

        $contextState = $data['contextState'] ?? null;
        if (!\is_array($contextState)) {
            throw new SnapshotHydrationException('Invalid snapshot: "contextState" must be present and must be an array.');
        }

        $currentStateId = $data['currentStateId'] ?? null;
        if ($currentStateId !== null && !\is_string($currentStateId)) {
            throw new SnapshotHydrationException('Invalid snapshot: "currentStateId" must be a string or null.');
        }

        $stateData = $data['stateData'] ?? null;
        if (!\is_array($stateData)) {
            throw new SnapshotHydrationException('Invalid snapshot: "stateData" must be present and must be an array.');
        }

        $tickCount = $data['tickCount'] ?? null;
        if (!\is_int($tickCount)) {
            throw new SnapshotHydrationException('Invalid snapshot: "tickCount" must be an integer.');
        }

        $createdAt = $data['createdAt'] ?? null;
        if (!\is_string($createdAt)) {
            throw new SnapshotHydrationException('Invalid snapshot: "createdAt" must be an ISO-8601 string.');
        }

        $parsedCreatedAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $createdAt);
        if ($parsedCreatedAt === false) {
            throw new SnapshotHydrationException(\sprintf('Invalid snapshot: "createdAt" value "%s" is not ISO-8601.', $createdAt));
        }

        return self::create($contextState, $currentStateId, $stateData, $tickCount, $parsedCreatedAt);
    }

    /**
     * @return array{
     *     schemaVersion: int,
     *     createdAt: string,
     *     tickCount: int,
     *     currentStateId: string|null,
     *     contextState: array<string, mixed>,
     *     stateData: array<string, array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'createdAt' => $this->createdAt->format(DateTimeInterface::ATOM),
            'tickCount' => $this->tickCount,
            'currentStateId' => $this->currentStateId,
            'contextState' => $this->contextState,
            'stateData' => $this->stateData,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<array-key, mixed> $stateData
     *
     * @return array<string, array<string, mixed>>
     */
    private static function normalizeStateData(array $stateData): array
    {
        $normalized = [];

        foreach ($stateData as $stateId => $data) {
            if (!\is_string($stateId) || $stateId === '') {
                throw new SnapshotHydrationException('Invalid snapshot: "stateData" keys must be non-empty strings.');
            }

            if (!\is_array($data)) {
                throw new SnapshotHydrationException(\sprintf(
                    'Invalid snapshot: data for state "%s" must be an array.',
                    $stateId,
                ));
            }

            $normalized[$stateId] = StateNormalizer::normalizeMap($data, 'stateData.' . $stateId);
        }

        return $normalized;
    }
}
