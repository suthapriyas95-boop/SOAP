<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Model;

use PHPUnit\Framework\TestCase;

class AddressConverterTest extends TestCase
{

    /**
     * @var \Magento\Quote\Api\Data\AddressInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressInterfaceFactoryMock;

    /**
     * @var \Magento\Directory\Model\Region|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $regionMock;

    /**
     * @var \Magento\Quote\Api\Data\AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $magentoAddressMock;

    /**
     * @var AddressConverter
     */
    protected $converter;

    protected function setUp()
    {

        $this->addressInterfaceFactoryMock = $this->createMock(\Magento\Quote\Api\Data\AddressInterfaceFactory::class);
        $this->regionMock = $this->createMock(\Magento\Directory\Model\Region::class);

        $this->magentoAddressMock = $this->createMock(\Magento\Quote\Api\Data\AddressInterface::class);
        $this->addressInterfaceFactoryMock->method('create')->willReturn($this->magentoAddressMock);

        $this->converter = new AddressConverter($this->addressInterfaceFactoryMock, $this->regionMock);
    }

    public function testConvertGoogleAddress()
    {
        $data = [
            'countryCode' => 'US',
            'administrativeArea' => 'New York',
            'name' => 'N B',
            'address1' => '1 main st',
            'address2' => 'room 228',
            'address3' => 'a',
            'postalCode' => '10002',
            'locality' => 'New York',
            'emailAddress' => 'test@example.org',
            'phoneNumber' => '1234567890',
        ];

        $this->regionMock->method('loadByName')->with($data['administrativeArea'])->willReturnSelf();
        $this->regionMock->method('getId')->willReturn(11);

        $this->magentoAddressMock->expects(static::once())->method('setFirstname')->with('N')->willReturnSelf();
        $this->magentoAddressMock->expects(static::once())->method('setLastname')->with('B')->willReturnSelf();
        $this->magentoAddressMock->expects(static::once())->method('setPostcode')->with($data['postalCode'])->willReturnSelf();
        $this->magentoAddressMock->expects(static::once())->method('setStreet')->with([$data['address1'],$data['address2'],$data['address3']])->willReturnSelf();
        $this->magentoAddressMock->expects(static::once())->method('setCity')->with($data['locality'])->willReturnSelf();
        $this->magentoAddressMock->expects(static::once())->method('setEmail')->with($data['emailAddress'])->willReturnSelf();
        $this->magentoAddressMock->expects(static::once())->method('setTelephone')->with($data['phoneNumber'])->willReturnSelf();
        $this->magentoAddressMock->expects(static::once())->method('setCountryId')->with($data['countryCode'])->willReturnSelf();

        static::assertEquals($this->magentoAddressMock, $this->converter->convertGoogleAddress($data));

    }
}
