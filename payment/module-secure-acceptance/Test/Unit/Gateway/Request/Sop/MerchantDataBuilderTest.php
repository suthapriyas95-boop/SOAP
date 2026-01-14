<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class MerchantDataBuilderTest extends TestCase
{
    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var MerchantDataBuilder */
    private $merchantDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var \Magento\Framework\Locale\Resolver | \PHPUnit_Framework_MockObject_MockObject */
    private $localeResolverMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->localeResolverMock = $this->createMock(\Magento\Framework\Locale\Resolver::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);

        $this->merchantDataBuilder = new MerchantDataBuilder(
            $this->subjectReaderMock,
            $this->configMock,
            $this->localeResolverMock
        );
    }

    public function testBuild()
    {

        $expected = [
            'access_key' => 'some_access_key',
            'profile_id' => 'some_profile_id',
            'partner_solution_id' => \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID,
            'locale' => 'en-us',
        ];
        $storeId = 233;

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->method('isSilent')->willReturn(false);

        $this->configMock->method('getAuthAccessKey')->with($storeId)->willReturn($expected['access_key']);
        $this->configMock->method('getAuthProfileId')->with($storeId)->willReturn($expected['profile_id']);

        $this->localeResolverMock->method('getLocale')->willReturn('en_US');

        $this->assertEquals($expected, $this->merchantDataBuilder->build($subject));
    }
    
}
