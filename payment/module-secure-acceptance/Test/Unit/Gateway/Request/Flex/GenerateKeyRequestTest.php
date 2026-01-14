<?php
/**
 *
 */

namespace Cybersource\SecureAcceptance\Gateway\Request\Flex;

use CyberSource\SecureAcceptance\Gateway\Request\Flex\GenerateKeyRequest;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class GenerateKeyRequestTest extends TestCase
{
    /**
     * @var \Magento\Store\Api\Data\StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Request\Flex\GenerateKeyRequest
     */
    private $subject;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    protected function setUp()
    {
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->storeMock = $this->getMockForAbstractClass(
            \Magento\Store\Api\Data\StoreInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getBaseUrl']);

        $this->subject = new GenerateKeyRequest(
            $this->storeManagerMock
        );

    }

    /**
     * @dataProvider dataProviderTestBuild
     * @param $expected
     */
    public function testBuild($expected)
    {

        $this->storeManagerMock->expects(static::any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects(static::any())->method('getBaseUrl')->willReturn('https://example.org/');

        static::assertEquals($expected, $this->subject->build([]));

    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'expected' => [
                    'encryptionType' => 'RsaOaep256',
                    'targetOrigin' => 'https://example.org',
                ]
            ]
        ];
    }




}
