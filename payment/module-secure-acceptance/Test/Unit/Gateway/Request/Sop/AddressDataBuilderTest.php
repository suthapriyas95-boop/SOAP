<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class AddressDataBuilderTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Data\AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressMock;

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
            'forename' => 'test',
            'surname' => 'example',
            'company_name' => 'example org',
            'email' => 'test@example.org',
            'address_line1' => 'test line 1',
            'address_line2' => 'line 2',
            'address_city' => 'TestVille',
            'address_state' => 'Testorado',
            'address_country' => 'TE',
            'address_postal_code' => '12312312',
            'phone' => '123123',
        ];

        $this->addressMock->method('getFirstname')->willReturn($address['forename']);
        $this->addressMock->method('getLastname')->willReturn($address['surname']);
        $this->addressMock->method('getCompany')->willReturn($address['company_name']);
        $this->addressMock->method('getEmail')->willReturn($address['email']);
        $this->addressMock->method('getStreetLine1')->willReturn($address['address_line1']);
        $this->addressMock->method('getStreetLine2')->willReturn($address['address_line2']);
        $this->addressMock->method('getCity')->willReturn($address['address_city']);
        $this->addressMock->method('getRegionCode')->willReturn($address['address_state']);
        $this->addressMock->method('getCountryId')->willReturn($address['address_country']);
        $this->addressMock->method('getPostcode')->willReturn($address['address_postal_code']);
        $this->addressMock->method('getTelephone')->willReturn($address['phone']);

        $this->orderMock->method('getBillingAddress')->willReturn($this->addressMock);
        $this->orderMock->method('getShippingAddress')->willReturn($this->addressMock);

        $expected = [];

        foreach (['bill_to_', 'ship_to_'] as $prefix) {
            foreach ($address as $path => $addressItem) {
                $expected[$prefix . $path] = $addressItem;
            }
        }

        $this->assertEquals($expected, $this->addressDataBuilder->build($subject));
    }

    public function testMissing()
    {
        $this->fail('Test not yet implemented');
    }
}
