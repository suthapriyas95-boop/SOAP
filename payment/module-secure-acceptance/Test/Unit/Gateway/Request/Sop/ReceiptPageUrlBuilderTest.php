<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class ReceiptPageUrlBuilderTest extends TestCase
{
    /** @var ReceiptPageUrlBuilder */
    private $receiptPageUrlBuilder;

    /** @var \Magento\Framework\UrlInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $urlBuilderMock;

    protected function setUp()
    {
        $this->urlBuilderMock = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->receiptPageUrlBuilder = new ReceiptPageUrlBuilder(
            $this->urlBuilderMock
        );
    }

    public function testBuild()
    {
        $expected = [
            'override_custom_receipt_page' => 'https://example.org/cybersource/index/placeorder',
            'override_custom_cancel_page' => 'https://example.org/cybersource/index/cancel',
        ];

        $this->urlBuilderMock->method('getUrl')->willReturnMap(
            [
                ['cybersource/index/placeorder', ['_secure' => true], $expected['override_custom_receipt_page']],
                ['cybersource/index/cancel', ['_secure' => true], $expected['override_custom_cancel_page']],
            ]
        );

        $this->assertEquals($expected, $this->receiptPageUrlBuilder->build([]));
    }

}
