<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class AddressDataBuilderTest extends TestCase
{
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

    /** @var AddressDataBuilder */
    private $addressDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /**
     * @var \Magento\Payment\Gateway\Data\AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);
        $this->addressMock = $this->createMock(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->expects(static::any())->method('getBillingAddress')->willReturn($this->addressMock);

        $this->addressDataBuilder = new AddressDataBuilder(
            $this->subjectReaderMock
        );
    }

    public function testBuild()
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $address = [
            'firstName' => 'test',
            'lastName' => 'example',
            'company' => 'example org',
            'email' => 'test@example.org',
            'street1' => 'test line 1',
            'street2' => 'line 2',
            'city' => 'TestVille',
            'state' => 'Testorado',
            'country' => 'TE',
            'phoneNumber' => '123123',
            'postalCode' => '12312312',
        ];

        $this->addressMock->method('getFirstname')->willReturn($address['firstName']);
        $this->addressMock->method('getLastname')->willReturn($address['lastName']);
        $this->addressMock->method('getCompany')->willReturn($address['company']);
        $this->addressMock->method('getEmail')->willReturn($address['email']);
        $this->addressMock->method('getStreetLine1')->willReturn($address['street1']);
        $this->addressMock->method('getStreetLine2')->willReturn($address['street2']);
        $this->addressMock->method('getCity')->willReturn($address['city']);
        $this->addressMock->method('getRegionCode')->willReturn($address['state']);
        $this->addressMock->method('getCountryId')->willReturn($address['country']);
        $this->addressMock->method('getTelephone')->willReturn($address['phoneNumber']);
        $this->addressMock->method('getPostcode')->willReturn($address['postalCode']);

        $this->orderMock->method('getBillingAddress')->willReturn($this->addressMock);
        $this->orderMock->method('getShippingAddress')->willReturn($this->addressMock);

        $this->assertEquals([
            'billTo' => $address,
            'shipTo' => $address,
        ], $this->addressDataBuilder->build($subject));
    }
}
