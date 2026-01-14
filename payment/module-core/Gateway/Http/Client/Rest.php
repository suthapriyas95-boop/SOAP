<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Http\Client;

use Exception;
use CyberSource\Core\Gateway\Http\Client\Rest\RequestSigner;
use CyberSource\Core\Model\Config;
use CyberSource\Core\Model\LoggerInterface;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Magento\Framework\Serialize\Serializer;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class Rest implements ClientInterface
{
    const API_HOST = 'api.cybersource.com';
    const TEST_API_HOST = 'apitest.cybersource.com';

    const KEY_URL_PARAMS = 'url_params';

    const CONTENT_TYPES_JSON = [ 'application/json', 'application/hal+json'];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var RequestSigner
     */
    private $requestSigner;

    /**
     * @var string
     */
    private $requestPath;

    /**
     * @var string
     */
    private $requestMethod;

    /**
     * @var int|null
     */
    private $storeId = null;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @param Config $config
     * @param LoggerInterface $logger
     * @param RequestSigner $requestSigner
     * @param Serializer\Json $jsonSerializer
     * @param Client $httpClientFactory
     * @param null $storeId
     * @param null $requestPath
     * @param null $requestMethod
     * @param string $contentType
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        RequestSigner $requestSigner,
        Serializer\Json $jsonSerializer,
        Client $httpClientFactory,
        $storeId = null,
        $requestPath = null,
        $requestMethod = null,
        $contentType = 'application/json'
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->requestSigner = $requestSigner;
        $this->jsonSerializer = $jsonSerializer;
        $this->client = $httpClientFactory;
        $this->requestPath = $requestPath;
        $this->requestMethod = $requestMethod;
        $this->storeId = $storeId;
        $this->contentType = $contentType;
    }

    /**
     * @param TransferInterface $transferObject
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $requestMethod = $this->requestMethod ?: $transferObject->getMethod();

        if (! $this->isAcceptableMethod($requestMethod)) {
            throw new \InvalidArgumentException('method is unknown.');
        }

        $payload = $transferObject->getBody();
        $requestPath = $this->requestPath ?: $transferObject->getUri();
        $endpointUrl = 'https://' . $this->getApiHost() . $requestPath;

        if ($uriParams = $payload[static::KEY_URL_PARAMS] ?? null) {
            $urlPart = '/' . implode('/', $uriParams);
            $endpointUrl .= $urlPart;
            $requestPath .= $urlPart;
            unset($payload[static::KEY_URL_PARAMS]);
        }

        $this->client->setMethod($requestMethod);
        $this->client->setUri($endpointUrl);

        $query = '';

        $log = [
            'client' => static::class,
            'endpoint' => $endpointUrl,
            'method' => $requestMethod,
            'request' => $payload,
        ];

        if ($requestMethod == Request::METHOD_GET) {
            $query = '?' . http_build_query($payload);
            $this->client->setParameterGet($payload);
        } else {
            $payload = $this->jsonSerializer->serialize($payload);
            $this->client->setRawBody($payload);
        }

        $this->client->getUri();

        $signedHeaders = $this->requestSigner->getSignedHeaders(
            $this->getApiHost(),
            $requestMethod,
            $requestPath . $query,
            $payload,
            $this->storeId,
            $this->contentType
        );

        $this->client->setHeaders($signedHeaders);

        $response = [];

        try {
            $response = $this->client->send();

            $responseBody = $response->getBody();

            try {
                $responseBody = $this->jsonSerializer->unserialize($responseBody);
            } catch (\InvalidArgumentException $e) {
                $responseBody = ['response' => $responseBody];
            }

            $response = array_merge(
                [
                    'http_code' => $response->getStatusCode(),
                    'http_message' => $response->getReasonPhrase(),
                ],
                $responseBody
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        } finally {
            $log['response'] = $response;
            $this->logger->debug($log);
        }

        return $response;
    }

    /**
     * @param string $requestPath
     * @return Rest
     */
    public function setRequestPath($requestPath)
    {
        $this->requestPath = $requestPath;
        return $this;
    }

    /**
     * @param string $requestMethod
     * @return Rest
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
        return $this;
    }

    /**
     * @param int|null $storeId
     *
     * @return \CyberSource\Core\Gateway\Http\Client\Rest
     */
    public function setStoreId(int $storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @param string $method
     * @return bool
     */
    private function isAcceptableMethod($method)
    {
        return in_array(
            $method,
            [
                Request::METHOD_GET,
                Request::METHOD_DELETE,
                Request::METHOD_POST,
                Request::METHOD_PUT
            ]
        );
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    private function getApiHost($storeId = null)
    {
        return $this->config->getUseTestWsdl($storeId) ? self::TEST_API_HOST : self::API_HOST;
    }

}
