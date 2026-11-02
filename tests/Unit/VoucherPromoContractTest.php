<?php

declare(strict_types=1);

// Package: uzapoint/reporting-contracts — tests/Unit/VoucherPromoContractTest.php

namespace Uzapoint\ReportingContracts\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Uzapoint\ReportingContracts\Sales\SaleReportData;
use Uzapoint\ReportingContracts\Tests\Fixtures\Payloads;
use Uzapoint\ReportingContracts\Tests\TestCase;
use Uzapoint\ReportingContracts\Vouchers\PromoRedemptionReportData;
use Uzapoint\ReportingContracts\Vouchers\VoucherReportData;
use Uzapoint\ReportingContracts\Vouchers\VoucherTransactionReportData;

/** Voucher and promo reporting: new contracts, and the additive fields on the sale snapshot (schema 1). */
final class VoucherPromoContractTest extends TestCase
{
    #[Test]
    public function old_sale_payloads_default_the_voucher_and_promo_fields(): void
    {
        $wire = SaleReportData::from(Payloads::sale())->toReportPayload();

        $this->assertSame(0.0, (float) $wire['promo_discount_total']);
        $this->assertSame(0.0, (float) $wire['voucher_paid_total']);
        $this->assertNull($wire['promo']);
        $this->assertFalse($wire['payments'][0]['is_voucher']);
        $this->assertNull($wire['payments'][0]['reference']);
    }

    #[Test]
    public function a_sale_carries_its_promo_and_voucher_tender(): void
    {
        $payload = Payloads::sale();
        $payload['order_discount_total'] = 161;
        $payload['promo_discount_total'] = 161;
        $payload['voucher_paid_total'] = 500;
        $payload['promo'] = ['ref' => '0f8fad5b-d9cb-469f-a165-70867728950e', 'code' => 'SAVE10', 'discount' => 161, 'name' => 'Ten percent off'];
        $payload['payments'][] = ['payment_method' => 'Voucher', 'amount' => 500, 'is_voucher' => true, 'reference' => 'hold-ref-1'];

        $wire = SaleReportData::from($payload)->toReportPayload();

        $this->assertSame('SAVE10', $wire['promo']['code']);
        $this->assertSame(161.0, (float) $wire['promo']['discount']);
        $this->assertFalse($wire['promo']['stackable']);
        $this->assertTrue($wire['payments'][1]['is_voucher']);
        $this->assertSame('hold-ref-1', $wire['payments'][1]['reference']);
        $this->assertSame(500.0, (float) $wire['voucher_paid_total']);
    }

    #[Test]
    public function the_voucher_snapshot_never_exposes_a_full_code(): void
    {
        $wire = VoucherReportData::from(Payloads::all()[VoucherReportData::class])->toReportPayload();

        $this->assertArrayNotHasKey('code', $wire);
        $this->assertSame('7QK2', $wire['code_hint']);
    }

    #[Test]
    public function the_voucher_snapshot_is_dated_by_its_latest_activity_else_issue_time(): void
    {
        $voucher = VoucherReportData::from(Payloads::all()[VoucherReportData::class]);

        $this->assertSame((new \DateTimeImmutable('2026-10-27T15:10:00+03:00'))->getTimestamp(), $voucher->occurredAt()->getTimestamp());

        $fresh = VoucherReportData::from(['last_activity_at' => null] + Payloads::all()[VoucherReportData::class]);

        $this->assertSame((new \DateTimeImmutable('2026-10-20T10:00:00+03:00'))->getTimestamp(), $fresh->occurredAt()->getTimestamp());
    }

    #[Test]
    public function a_voucher_transaction_is_idempotent_per_transaction(): void
    {
        $transaction = VoucherTransactionReportData::from(Payloads::all()[VoucherTransactionReportData::class]);

        $this->assertSame('voucher:txn:31:880', $transaction->idempotencyKey());
        $this->assertSame('voucher', $transaction->aggregateType());
        $this->assertSame('31', (string) $transaction->aggregateId());
    }

    #[Test]
    public function a_promo_reversal_is_a_separate_idempotent_message_from_its_redemption(): void
    {
        $payload = Payloads::all()[PromoRedemptionReportData::class];

        $redeemed = PromoRedemptionReportData::from($payload);
        $reversed = PromoRedemptionReportData::from(['action' => 'reversed'] + $payload);

        $this->assertSame('promo:redeemed:410', $redeemed->idempotencyKey());
        $this->assertSame('promo:reversed:410', $reversed->idempotencyKey());
        $this->assertSame($redeemed->aggregateId(), $reversed->aggregateId());
    }

    #[Test]
    public function a_promo_redemption_can_be_published_before_the_sale_exists(): void
    {
        $payload = Payloads::all()[PromoRedemptionReportData::class];
        unset($payload['sale_id'], $payload['sale_number']);

        $wire = PromoRedemptionReportData::from($payload)->toReportPayload();

        $this->assertNull($wire['sale_id']);
        $this->assertSame('0f8fad5b-d9cb-469f-a165-70867728950e', $wire['sale_ref']);
    }
}
