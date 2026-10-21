<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Procurement;

use DateTimeInterface;
use SammyBoy\Reporting\Envelope\ReportKind;
use Spatie\LaravelData\Attributes\MapName;
use Uzapoint\ReportingContracts\ReportData;

/**
 * Snapshot of a supplier.
 *
 * Routing keys: procurement.supplier.created | procurement.supplier.updated  (procurement.events)
 * Consumer:     reports-ms HandleProcurementSupplier -> procurement_suppliers
 *               (create on "created", update by supplier_id + business_id on "updated").
 */
final class SupplierReportData extends ReportData
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(
        /** Wire name is "id". */
        #[MapName('id')]
        public int $supplier_id,
        public int $business_id,
        /** created | updated */
        public string $action,
        public string $name,
        /** ISO-8601 */
        public string $created_at,
        public string $code = '',
        public string $email = '',
        public string $phone = '',
        public string $address = '',
        public string $contact_person = '',
        public string $status = 'active',
        public ?string $updated_at = null,
        public int|string|null $user_id = null,
    ) {}

    public function eventName(): string
    {
        return "procurement.supplier.{$this->action}";
    }

    public function kind(): ReportKind
    {
        return ReportKind::Snapshot;
    }

    public function schemaVersion(): int
    {
        return self::SCHEMA_VERSION;
    }

    public function aggregateType(): string
    {
        return 'supplier';
    }

    public function aggregateId(): string|int
    {
        return $this->supplier_id;
    }

    public function occurredAt(): ?DateTimeInterface
    {
        return $this->timestamp($this->action === 'updated' ? $this->updated_at : $this->created_at);
    }

    public function actorId(): int|string|null
    {
        return $this->user_id;
    }
}
