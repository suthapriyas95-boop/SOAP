<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Http\Client;

use CyberSource\Core\Model\LoggerInterface;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use Laminas\Http\Client;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

//@TODO: find out if we really need second client
class HTTPClient implements ClientInterface
{
    const POST = "POST";

    /**
     * @var \Laminas\Http\Client;
     */
    protected $httpClientFactory;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var  Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * HTTPClient constructor.
     * @param \Laminas\Http\Client $httpClientFactory
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Laminas\Http\Client $httpClientFactory,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->createClient();
    }

    /**
     * @param TransferInterface $transferObject
     * @return \Laminas\Http\Response|array
     * @throws \Exception
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        if ($transferObject->getUri() === "" && $transferObject->getMethod() === "") {
            return $transferObject->getBody();
        }

        $request = json_decode($transferObject->getBody(), true);

        $this->client->setMethod($transferObject->getMethod());
        $this->client->setUri($transferObject->getUri());
        $this->client->setParameterPost($request);

        $log = [
            'request' => (array) $request,
            'client' => static::class
        ];

        $response = [];

        try {
            $httpResponse = $this->client->request();

            $dom = new \DOMDocument();
            $dom->loadHTML($httpResponse->getBody());
            $nodes = $dom->getElementsByTagName("input");

            /** @var \DOMElement $node */
            foreach ($nodes as $node) {
                $response[$node->getAttribute("name")] = $node->getAttribute("value");
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        } finally {
            $log['response'] = $response;
            $this->logger->debug($log);
        }

        return $response;
    }

    private function createClient()
    {
        /** @var Client $client */
        $this->client = $this->httpClientFactory;
    }
}
