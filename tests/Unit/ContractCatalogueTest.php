<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SammyBoy\Reporting\Contracts\Reportable;
use SammyBoy\Reporting\Envelope\ReportKind;
use SammyBoy\Reporting\Facades\Reporting;
use Uzapoint\ReportingContracts\ReportData;
use Uzapoint\ReportingContracts\Tests\Fixtures\Payloads;
use Uzapoint\ReportingContracts\Tests\TestCase;

/**
 * Invariants every publishable contract must hold, run against each one.
 */
final class ContractCatalogueTest extends TestCase
{
    /**
     * Every routing key the Reports MS subscribes to today (config/eventbus.php).
     * A contract whose eventName() is not in this list would publish into the void.
     */
    private const array CONSUMED_ROUTING_KEYS = [
        'business.deleted',
        'business.data.purged',
        'support_ticket.created',
        'support_ticket.updated',
        'support_ticket.closed',
        'ticket_message.created',
        'sales.item.voided',
        'sale.item.synced',
        'sale.payment.recorded',
        'invoice.snapshot.synced',
        'quotation.snapshot.synced',
        'sales.credit_note_issued',
        'procurement.requisition.created',
        'procurement.requisition.approved',
        'procurement.requisition.rejected',
        'procurement.purchase_order.created',
        'procurement.purchase_order.sent',
        'procurement.purchase_order.accepted',
        'procurement.purchase_order.rejected',
        'procurement.purchase_order.cancelled',
        'procurement.goods_received.grn_created',
        'procurement.goods_received.items_received',
        'procurement.rejected_item.recorded',
        'procurement.purchase_return.created',
        'procurement.purchase_return.submitted',
        'procurement.purchase_return.approved',
        'procurement.purchase_return.rejected',
        'procurement.catalogue.generated',
        'procurement.supplier.created',
        'procurement.supplier.updated',
        'products.skus.created',
        'products.skus.updated',
        'products.skus.deleted',
        'product.item.synced',
        'product.item.deactivated',
        'accounts.expense.created',
        'accounts.expense.approved',
        'accounts.expense.voided',
        'accounts.payable.snapshot',
        'accounts.receivable.snapshot',
        'payable.payment.recorded',
        'payable.fully_paid',
        'receivable.payment.recorded',
        'receivable.fully_paid',
        // Consumed by sales-ms (accounts.events -> sales_ms.accounts_events), not Reports MS.
        'receivable.payment.reversed',
        'stock.transaction.recorded',
        // Consumed by reports-ms from business.events (business-ms commissions).
        'commission.ledger.recorded',
        'production.requisition.synced',
        'production.finished_goods.produced',
        'production.raw_materials.consumed',
    ];

    /**
     * @return iterable<string, array{class-string<ReportData>, array<string, mixed>}>
     */
    public static function contracts(): iterable
    {
        foreach (Payloads::all() as $class => $payload) {
            yield class_basename($class) => [$class, $payload];
        }
    }

    #[Test]
    public function every_publishable_contract_in_src_has_a_fixture(): void
    {
        $classes = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src')) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen(dirname(__DIR__, 2) . '/src/'), -4);
            $class = 'Uzapoint\\ReportingContracts\\' . str_replace('/', '\\', $relative);

            if (is_subclass_of($class, ReportData::class) && !(new \ReflectionClass($class))->isAbstract()) {
                $classes[] = $class;
            }
        }

        sort($classes);
        $fixtures = array_keys(Payloads::all());
        sort($fixtures);

        $this->assertSame($fixtures, $classes, 'Add a fixture to Payloads::all() for every new contract.');
        $this->assertCount(24, $classes);
    }

    #[Test]
    #[DataProvider('contracts')]
    public function hydrates_and_declares_a_valid_identity(string $class, array $payload): void
    {
        $contract = $class::from($payload);

        $this->assertInstanceOf(Reportable::class, $contract);
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', $contract->eventName());
        $this->assertContains($contract->eventName(), self::CONSUMED_ROUTING_KEYS, "{$class} publishes a key nobody consumes");
        $this->assertInstanceOf(ReportKind::class, $contract->kind());
        $this->assertGreaterThanOrEqual(1, $contract->schemaVersion());
        $this->assertSame($class::SCHEMA_VERSION, $contract->schemaVersion());
        $this->assertNotSame('', trim($contract->aggregateType()));
        $this->assertNotSame('', trim((string) $contract->aggregateId()));
    }

    #[Test]
    #[DataProvider('contracts')]
    public function payload_is_json_safe_and_round_trips(string $class, array $payload): void
    {
        $contract = $class::from($payload);
        $wire = $contract->toReportPayload();

        $this->assertArrayNotHasKey('_reporting', $wire);

        // What eventbus-core does to it, including int-for-whole-float.
        $decoded = json_decode(json_encode($wire, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        $rehydrated = $class::from($decoded);

        $this->assertEquals($contract->toReportPayload(), $rehydrated->toReportPayload());
        $this->assertSame($contract->eventName(), $rehydrated->eventName());
        $this->assertSame((string) $contract->aggregateId(), (string) $rehydrated->aggregateId());
        $this->assertSame($contract->idempotencyKey(), $rehydrated->idempotencyKey());
    }

    #[Test]
    #[DataProvider('contracts')]
    public function ignores_unknown_wire_fields_for_forward_compatibility(string $class, array $payload): void
    {
        $contract = $class::from($payload + ['field_added_in_a_future_version' => 'x', '_reporting' => ['ignored' => true]]);

        $this->assertArrayNotHasKey('field_added_in_a_future_version', $contract->toReportPayload());
        $this->assertArrayNotHasKey('_reporting', $contract->toReportPayload());
    }

    #[Test]
    #[DataProvider('contracts')]
    public function publishes_through_laravel_reporting(string $class, array $payload): void
    {
        $fake = Reporting::fake();
        $contract = $class::from($payload);

        $envelope = Reporting::publish($contract);

        $fake->assertPublished($contract->eventName());
        $this->assertSame($contract->kind(), $envelope->kind);
        $this->assertSame($contract->schemaVersion(), $envelope->schemaVersion);
        $this->assertSame((string) $contract->aggregateId(), $envelope->aggregateId);

        if ($contract->idempotencyKey() !== null) {
            $this->assertSame($contract->idempotencyKey(), $envelope->eventId);
        }

        if ($contract->occurredAt() !== null) {
            $this->assertSame($contract->occurredAt()->getTimestamp(), $envelope->occurredAt->getTimestamp());
        }
    }
}
