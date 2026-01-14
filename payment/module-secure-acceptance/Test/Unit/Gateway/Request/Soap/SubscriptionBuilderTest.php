<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class SubscriptionBuilderTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var SubscriptionBuilder */
    private $subscriptionBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement | \PHPUnit_Framework_MockObject_MockObject */
    private $paymentTokenManagementMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->paymentTokenManagementMock = $this->createMock(\CyberSource\SecureAcceptance\Model\PaymentTokenManagement::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->subscriptionBuilder = new SubscriptionBuilder(
            $this->subjectReaderMock,
            $this->paymentTokenManagementMock
        );
    }

    public function testBuild($expected = [])
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->willReturn($subject['payment']);

        $token = '123123123123';

        $this->paymentTokenManagementMock->method('getTokenFromPayment')->with($this->paymentMock)->willReturn($token);

        $this->assertEquals(
            [
                'recurringSubscriptionInfo' => [
                    'subscriptionID' => $token,
                ],
            ],
            $this->subscriptionBuilder->build($subject)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Subscription Id must be provided
     */
    public function testBuildWithException()
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->willReturn($subject['payment']);

        $this->assertEquals([], $this->subscriptionBuilder->build($subject));
    }
}
