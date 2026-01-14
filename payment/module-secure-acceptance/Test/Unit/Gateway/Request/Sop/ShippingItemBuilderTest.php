<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class ShippingItemBuilderTest extends TestCase
{
    /** @var ShippingItemBuilder */
    private $shippingItemBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->shippingItemBuilder = new ShippingItemBuilder(
            $this->subjectReader
        );
    }

    public function testMissing()
    {
        $this->fail('Test not yet implemented');
    }
}
