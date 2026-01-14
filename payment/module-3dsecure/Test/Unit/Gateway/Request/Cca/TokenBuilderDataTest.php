<?php declare(strict_types=1);

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

use PHPUnit\Framework\TestCase;

class TokenBuilderDataTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Data\AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $billingAddressMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var TokenDataBuilder */
    private $tokenDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var \Magento\Framework\Math\Random | \PHPUnit_Framework_MockObject_MockObject */
    private $random;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->random = $this->createMock(\Magento\Framework\Math\Random::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);
        $this->billingAddressMock = $this->createMock(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->expects(static::any())->method('getBillingAddress')->willReturn($this->billingAddressMock);

        $this->tokenDataBuilder = new TokenDataBuilder(
            $this->subjectReader,
            $this->random
        );
    }

    public function testBuild()
    {

        $subject = [
            'amount' => 1.99,
            'payment' => $this->paymentDOMock,
            'cardBin' => '411111'
        ];

        $this->subjectReader->expects(static::any())->method('readPayment')->with($subject)->willReturn($subject['payment']);
        $this->subjectReader->expects(static::any())->method('readAmount')->with($subject)->willReturn($subject['amount']);

        $this->random->expects(static::any())->method('getUniqueHash')->with('order_')->willReturn('order_123');

        $this->orderMock->expects(static::any())->method('getCurrencyCode')->willReturn('USD');

        $expectedBilling = [
            'FirstName' => 'Test',
            'LastName' => 'Example',
            'Address1' => 'Test street',
            'Address2' => 'line 2',
            'City' => 'TestVille',
            'State' => 'Testorado',
            'CountryCode' => 'US',
            'Phone1' => '1234567890',
            'PostalCode' => '12345'
        ];

        $expectedResult = [
            'OrderDetails' => [
                'OrderNumber' => 'order_123',
                'Amount' => 199.0,
                'CurrencyCode' => 'USD',
                'OrderChannel' => 'S',
            ],
            'Consumer' => [
                'Email1' => 'test@example.org',
                'BillingAddress' => $expectedBilling,
                'ShippingAddress' => $expectedBilling,
                'Account' => [
                    'AccountNumber' => $subject['cardBin']
                ]
            ]
        ];

        $this->billingAddressMock->expects(static::any())->method('getFirstname')->willReturn($expectedBilling['FirstName']);
        $this->billingAddressMock->expects(static::any())->method('getLastname')->willReturn($expectedBilling['LastName']);
        $this->billingAddressMock->expects(static::any())->method('getStreetLine1')->willReturn($expectedBilling['Address1']);
        $this->billingAddressMock->expects(static::any())->method('getStreetLine2')->willReturn($expectedBilling['Address2']);
        $this->billingAddressMock->expects(static::any())->method('getCity')->willReturn($expectedBilling['City']);
        $this->billingAddressMock->expects(static::any())->method('getRegionCode')->willReturn($expectedBilling['State']);
        $this->billingAddressMock->expects(static::any())->method('getCountryId')->willReturn($expectedBilling['CountryCode']);
        $this->billingAddressMock->expects(static::any())->method('getTelephone')->willReturn($expectedBilling['Phone1']);
        $this->billingAddressMock->expects(static::any())->method('getPostcode')->willReturn($expectedBilling['PostalCode']);

        $this->billingAddressMock->expects(static::any())->method('getEmail')->willReturn('test@example.org');

        $this->orderMock->expects(static::any())->method('getShippingAddress')->willReturn($this->billingAddressMock);

        $this->assertEquals($expectedResult, $this->tokenDataBuilder->build($subject));
    }
}
