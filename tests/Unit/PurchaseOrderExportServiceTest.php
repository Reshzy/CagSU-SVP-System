<?php

namespace Tests\Unit;

use App\Services\PurchaseOrderExportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PurchaseOrderExportServiceTest extends TestCase
{
    public function test_it_formats_amount_in_words_without_only_and_with_nn_over_100_centavos(): void
    {
        $service = new PurchaseOrderExportService;

        $method = new ReflectionMethod($service, 'convertNumberToWords');
        $method->setAccessible(true);

        $this->assertSame(
            'One Thousand Two Hundred Thirty Four Pesos & 00/100',
            $method->invoke($service, 1234.00)
        );

        $this->assertSame(
            'One Thousand Two Hundred Thirty Four Pesos & 50/100',
            $method->invoke($service, 1234.50)
        );

        // Rounding edge case (centavos can round up to 100).
        $this->assertSame(
            'Two Pesos & 00/100',
            $method->invoke($service, 1.999999)
        );
    }
}
