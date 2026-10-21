<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts;

use DateTimeImmutable;
use DateTimeInterface;
use SammyBoy\Reporting\Contracts\Reportable;
use Spatie\LaravelData\Data;

/**
 * Base class for every UzaPoint reporting contract.
 *
 * A contract is a spatie/laravel-data object that also satisfies
 * laravel-reporting's Reportable interface, so the same class is:
 *
 *   - what a producer constructs and hands to Reporting::publish()
 *   - what the Reports service hydrates with ::from($incoming->payload)
 *
 * Wire shape rules, applied by every subclass:
 *
 *   - Property names are the wire names (snake_case). Where the consumer's
 *     established wire name differs from a sensible property name, #[MapName]
 *     carries the wire name so producer output and consumer input both match
 *     the existing handler — routing keys and field names are never renamed.
 *   - Dates and timestamps are ISO-8601 strings, never DateTime objects, so a
 *     contract serialises identically regardless of the host app's data.php.
 *   - Money and quantities are floats. eventbus-core encodes whole floats as
 *     JSON integers; PHP widens them back on hydration.
 *   - Optional means "may be absent on the wire". Nullable means "present,
 *     may be null". Consumers using array_filter-style partial upserts rely
 *     on this distinction (e.g. items on a status-only invoice publish).
 *
 * Subclasses override occurredAt(), idempotencyKey() and actorId() only when
 * they carry that information.
 */
abstract class ReportData extends Data implements Reportable
{
    public function occurredAt(): ?DateTimeInterface
    {
        return null;
    }

    public function idempotencyKey(): ?string
    {
        return null;
    }

    public function actorId(): int|string|null
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toReportPayload(): array
    {
        return $this->toArray();
    }

    /**
     * For occurredAt() implementations backed by an ISO-8601 string property.
     */
    protected function timestamp(?string $value): ?DateTimeInterface
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
