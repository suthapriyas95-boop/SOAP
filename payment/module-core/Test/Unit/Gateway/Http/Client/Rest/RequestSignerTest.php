<?php declare(strict_types=1);

namespace CyberSource\Core\Gateway\Http\Client\Rest;

use PHPUnit\Framework\TestCase;

class RequestSignerTest extends TestCase
{
    /** @var RequestSigner */
    private $requestSigner;

    /** @var \CyberSource\Core\Model\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeMock;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\CyberSource\Core\Model\Config::class);
        $this->dateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->requestSigner = new RequestSigner(
            $this->dateTimeMock,
            $this->configMock
        );
    }

    /**
     * @param $input
     * @param $expectedResult
     * @dataProvider dataProviderTestBuild
     */
    public function testBuild($input, $expectedResult)
    {

        $keyId = '1234567890';
        $date = 'Wed, 1 Jan 2020 0:0:00 GMT';

        $this->configMock->expects(static::any())->method('getRestKeyId')->willReturn($keyId);
        $this->dateTimeMock->expects(static::any())->method('gmtDate')->with('D, d M Y G:i:s \\G\\M\\T')->willReturn($date);

        $this->configMock->expects(static::any())->method('getMerchantId')->willReturn($input['mid']);

        static::assertEquals(
            $expectedResult,
            $this->requestSigner->getSignedHeaders(
                $input['host'],
                $input['method'],
                $input['requestPath'],
                $input['payload'])
        );
    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'input' => [
                    'mid' => 'test',
                    'host' => 'example.org',
                    'method' => 'GET',
                    'requestPath' => '/test',
                    'payload' => 'some',
                ],
                'expectedResult' => [
                    'Content-Type: application/json',
                    'Date:Wed, 1 Jan 2020 0:0:00 GMT',
                    'Host:example.org',
                    'v-c-merchant-id: test',
                    'Signature: keyid="1234567890", algorithm="HmacSHA256", headers="host date request-target v-c-merchant-id", signature="Eg2Z+np9T1ZUoFHvhjLs+tlYfDqjy+HglezjFUvWmTc="',
                ]
            ],
            [
                'input' => [
                    'mid' => 'test',
                    'host' => 'example.org',
                    'method' => 'POST',
                    'requestPath' => '/test',
                    'payload' => 'somevalue',
                ],
                'expectedResult' => [
                    'Content-Type: application/json',
                    'Date:Wed, 1 Jan 2020 0:0:00 GMT',
                    'Host:example.org',
                    'v-c-merchant-id: test',
                    'Digest: SHA-256=cKUkaIztjkXSZ3b9TcVkEHJbVmzYQMBEVGqzDEtJk0I=',
                    'Signature: keyid="1234567890", algorithm="HmacSHA256", headers="host date request-target digest v-c-merchant-id", signature="bZOpSYRPCIJf5XZtGafo0I6li1ebaPhlRfb++9tVxzk="',
                ]
            ],
        ];
    }

}
