<?php declare(strict_types = 1);

namespace CyberSource\Core\Gateway\Http\Client;

use PHPUnit\Framework\TestCase;

class RestTest extends TestCase
{
    /** @var Rest */
    private $restClient;

    /** @var \CyberSource\Core\Model\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var \CyberSource\Core\Model\LoggerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    /** @var \CyberSource\Core\Gateway\Http\Client\Rest\RequestSigner | \PHPUnit_Framework_MockObject_MockObject */
    private $requestSignerMock;

    /** @var \Magento\Framework\Serialize\Serializer\Json | \PHPUnit_Framework_MockObject_MockObject */
    private $jsonSerializerMock;

    /** @var \Laminas\Http\Client | \PHPUnit_Framework_MockObject_MockObject */
    private $httpClientFactoryMock;

    /** @var mixed */
    private $requestPathMock;

    /** @var mixed */
    private $requestMethodMock;

    /**
     * @var \Magento\Payment\Gateway\Http\TransferInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transferObjectMock;

    /**
     * @var \Laminas\Http\Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpClientMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Laminas_Http_Response
     */
    private $responseMock;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\CyberSource\Core\Model\Config::class);
        $this->loggerMock = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);
        $this->requestSignerMock = $this->createMock(\CyberSource\Core\Gateway\Http\Client\Rest\RequestSigner::class);
        $this->jsonSerializerMock = $this->createMock(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->httpClientFactoryMock = $this->createMock(\Laminas\Http\Client::class);
        $this->requestPathMock = null;
        $this->requestMethodMock = null;

        $this->httpClientMock = $this->createMock(\Laminas\Http\Client::class);

        $this->httpClientFactoryMock->method('create')->willReturn($this->httpClientMock);

        $this->transferObjectMock = $this->createMock(\Magento\Payment\Gateway\Http\TransferInterface::class);

        $this->responseMock = $this->createMock(\Laminas_Http_Response::class);

        $this->httpClientMock->expects(static::any())->method('request')->willReturn($this->responseMock);

        $this->restClient = new Rest(
            $this->configMock,
            $this->loggerMock,
            $this->requestSignerMock,
            $this->jsonSerializerMock,
            $this->httpClientFactoryMock,
            $this->requestPathMock,
            $this->requestMethodMock
        );
    }

    /**
     * @param $method
     * @param $expectedResponse
     * @dataProvider dataProviderTestPlaceRequest
     *
     */
    public function testPlaceRequest($method, $requestPath, $rawBody, $httpCode, $httpMessage, $expectedResponse)
    {

        $headers = ['some'=> 'header'];
        $payload = ['param' => 'pam-pam'];

        $this->transferObjectMock->method('getBody')->willReturn($payload);
        $this->transferObjectMock->method('getUri')->willReturn($requestPath);

        $this->transferObjectMock->expects(static::any())->method('getMethod')->willReturn($method);

        $this->configMock->method('getUseTestWsdl')->willReturn(true);

        $this->httpClientMock->expects(static::once())->method('setMethod')->with($method)->willReturnSelf();

        if ($method == 'GET') {
            $this->httpClientMock->expects(static::once())->method('setParameterGet')->with($payload)->willReturnSelf();
        } else {
            $this->httpClientMock->expects(static::once())->method('setRawData')->with($payload)->willReturnSelf();
        }

        $this->httpClientMock->expects(static::once())->method('setUri')->with('https://' . 'apitest.cybersource.com' . $requestPath);

        $this->requestSignerMock->expects(static::any())->method('getSignedHeaders')->willReturn($headers);
        $this->jsonSerializerMock->expects(static::any())->method('serialize')->with($payload)->willReturn($payload);

        $this->httpClientMock->expects(static::once())->method('setHeaders')->with($headers);

        $this->responseMock->method('getBody')->willReturn($rawBody);
        $this->responseMock->method('getStatus')->willReturn($httpCode);
        $this->responseMock->method('getMessage')->willReturn($httpMessage);
        $this->jsonSerializerMock->method('unserialize')->with($rawBody)->willReturnCallback(function ($arg) {
            return json_decode($arg, true);
        });

        static::assertEquals($expectedResponse, $this->restClient->placeRequest($this->transferObjectMock));
    }

    public function dataProviderTestPlaceRequest()
    {
        return [
            [
                'method'=> 'POST',
                'requestPath'=> '/test',
                'rawBody' => json_encode(['some' => 'value']),
                'httpCode' => 200,
                'httpMessage' => 'OK',
                'expectedResponse' => [
                    'http_code' => 200,
                    'http_message' => 'OK',
                    'some' => 'value'
                ],
            ],
            [
                'method'=> 'GET',
                'requestPath'=> '/test?some=value',
                'rawBody' => json_encode(['some' => 'value']),
                'httpCode' => 200,
                'httpMessage' => 'OK',
                'expectedResponse' => [
                    'http_code' => 200,
                    'http_message' => 'OK',
                    'some' => 'value'
                ],
            ],
        ];
    }

}
