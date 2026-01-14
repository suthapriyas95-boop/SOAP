<?php declare(strict_types=1);

namespace CyberSource\ThreeDSecure\Model\Quote\Payment;

use PHPUnit\Framework\TestCase;

class ToOrderPaymentPluginTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Quote\Model\Quote\Payment\ToOrderPayment
     */
    private $toOrderPaymentMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Sales\Api\Data\OrderPaymentInterface
     */
    private $orderPaymentMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Quote\Model\Quote\Payment
     */
    private $quotePaymentMock;

    /**
     * @var ToOrderPaymentPlugin
     */
    private $toOrderPaymentPlugin;

    /** @var \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $orderPaymentExtensionFactory;

    protected function setUp()
    {
        $this->orderPaymentExtensionFactory = $this->createMock(\Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory::class);

        $this->toOrderPaymentPlugin = new ToOrderPaymentPlugin(
            $this->orderPaymentExtensionFactory
        );

        $this->toOrderPaymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment\ToOrderPayment::class);
        $this->orderPaymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->quotePaymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);
    }

    public function testAfterConvertSessionId()
    {
        $sessionId = 'testsessionid';

        $this->quotePaymentMock
            ->expects(static::any())
            ->method('getAdditionalInformation')
            ->with('payer_auth_enroll_session_id')
            ->willReturn($sessionId);

        $this->orderPaymentMock
            ->expects(static::atLeastOnce())
            ->method('setAdditionalInformation')
            ->with('payer_auth_enroll_session_id', $sessionId);

        $this->assertEquals(
            $this->orderPaymentMock,
            $this->toOrderPaymentPlugin->afterConvert(
                $this->toOrderPaymentMock,
                $this->orderPaymentMock,
                $this->quotePaymentMock
            )
        );
    }

    public function testAfterConvertExtensionAttributes()
    {

        $ccaResponse = 'someccaresponse';

        $orderExtensionAttributes = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderPaymentExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCcaResponse', 'getCcaResponse'])
            ->getMock();

        $quoteExtensionAttributes = $this->getMockBuilder(\Magento\Quote\Api\Data\PaymentExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCcaResponse', 'getCcaResponse'])
            ->getMock();

        $this->quotePaymentMock->expects(static::any())->method('getExtensionAttributes')->willReturn($quoteExtensionAttributes);
        $this->orderPaymentMock->expects(static::any())->method('getExtensionAttributes')->willReturn($orderExtensionAttributes);

        $quoteExtensionAttributes->expects(static::any())->method('getCcaResponse')->willReturn($ccaResponse);
        $orderExtensionAttributes->expects(static::once())->method('setCcaResponse')->with($ccaResponse);

        $this->assertEquals(
            $this->orderPaymentMock,
            $this->toOrderPaymentPlugin->afterConvert(
                $this->toOrderPaymentMock,
                $this->orderPaymentMock,
                $this->quotePaymentMock
            )
        );
    }
}
