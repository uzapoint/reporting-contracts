<?php

declare(strict_types=1);

namespace Uzapoint\ReportingContracts\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Uzapoint\ReportingContracts\Sales\SaleReportData;
use Uzapoint\ReportingContracts\Tests\Fixtures\Payloads;
use Uzapoint\ReportingContracts\Tests\TestCase;

/** Delivery, discount split and free goods ride on the sale snapshot (schema 1, additive). */
final class SalePricingContractTest extends TestCase
{
    #[Test]
    public function old_payloads_default_the_new_fields(): void
    {
        $wire = SaleReportData::from(Payloads::sale())->toReportPayload();

        $this->assertSame(0.0, (float) $wire['delivery_fee']);
        $this->assertSame(0.0, (float) $wire['order_discount_total']);
        $this->assertNull($wire['delivery']);
        $this->assertFalse($wire['items'][0]['is_free_good']);
        $this->assertFalse($wire['items'][0]['is_free_with_expense']);
    }

    #[Test]
    public function delivery_discounts_and_free_goods_round_trip(): void
    {
        $payload = Payloads::sale() + [];
        $payload['delivery_fee'] = 200;
        $payload['item_discount_total'] = 100;
        $payload['order_discount_total'] = 10;
        $payload['free_goods_value'] = 455;
        $payload['free_expense_total'] = 120;
        $payload['delivery'] = ['fee' => 200, 'list_fee' => 300, 'is_custom_fee' => true, 'zone_code' => 'Z2', 'location' => 'Westlands'];
        $payload['items'][0]['is_free_with_expense'] = true;
        $payload['items'][0]['is_free_good'] = true;
        $payload['items'][0]['expense_amount'] = 120;

        $wire = SaleReportData::from($payload)->toReportPayload();

        $this->assertSame(200.0, (float) $wire['delivery_fee']);
        $this->assertSame(300.0, (float) $wire['delivery']['list_fee']);
        $this->assertTrue($wire['delivery']['is_custom_fee']);
        $this->assertFalse($wire['delivery']['is_free']);
        $this->assertSame('Z2', $wire['delivery']['zone_code']);
        $this->assertTrue($wire['items'][0]['is_free_with_expense']);
        $this->assertSame(120.0, (float) $wire['items'][0]['expense_amount']);
        $this->assertSame(120.0, (float) $wire['free_expense_total']);
    }
}
