<?php

namespace CyberSource\Core\Gateway\Http\Client\Rest;

use CyberSource\Core\Model\Config;
use Laminas\Http\Request;

class RequestSigner
{
    const SIGNED_HEADERS_W_PAYLOAD = 'host date request-target digest v-c-merchant-id';
    const SIGNED_HEADERS = 'host date request-target v-c-merchant-id';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param Config $config
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        Config $config
    ) {
        $this->config = $config;
        $this->dateTime = $dateTime;
    }

    /**
     * @param string $host
     * @param string $method
     * @param string $requestPath
     * @param string $payload
     * @param null $storeId
     * @param string $contentType
     *
     * @return array
     */
    public function getSignedHeaders($host, $method, $requestPath, $payload, $storeId = null, $contentType = 'application/json')
    {

        $date = $this->dateTime->gmtDate("D, d M Y H:i:s \G\M\T");

        $headers = [
            'Content-Type: ' . $contentType,
            'Date:' . $date,
            'Host:' . $host,
            'v-c-merchant-id: ' . $this->config->getMerchantId($storeId)
        ];

        $signatureValueArray = [
            'host: ' . $host,
            'date: ' . $date,
            'request-target: ' . strtolower($method ?? '') . ' ' . $requestPath
        ];

        if ($this->isPayloadExpected($method)) {
            $payloadDigest = $this->generateDigest($payload);
            $signatureValueArray[] = "digest: " . $payloadDigest;
            $headers[] = 'Digest: ' . $payloadDigest;
        }

        $signatureValueArray[] = 'v-c-merchant-id: ' . $this->config->getMerchantId($storeId);

        $signatureValue = base64_encode(
            hash_hmac(
                'sha256',
                mb_convert_encoding(
                    implode("\n", $signatureValueArray),'UTF-8'
                ),
                base64_decode($this->config->getRestKeyValue($storeId)),
                true
            )
        );

        $signedHeaders = $this->isPayloadExpected($method) ? self::SIGNED_HEADERS_W_PAYLOAD : self::SIGNED_HEADERS;

        $finalSignature = [
            "keyid=\"{$this->config->getRestKeyId($storeId)}\"",
            "algorithm=\"HmacSHA256\"",
            "headers=\"{$signedHeaders}\"",
            "signature=\"{$signatureValue}\""
        ];

        $headers[] = 'Signature: ' . implode(", ", $finalSignature);

        return $headers;
    }

    /**
     * @param string $payload
     * @return string
     */
    private function generateDigest($payload)
    {
        $digestHash = hash("sha256", mb_convert_encoding($payload,'UTF-8'), true);
        return 'SHA-256=' . base64_encode($digestHash);
    }

    /**
     * @param string $method
     * @return bool
     */
    private function isPayloadExpected($method)
    {
        return in_array(
            $method,
            [
                Request::METHOD_PUT,
                Request::METHOD_POST
            ]
        );
    }
}
