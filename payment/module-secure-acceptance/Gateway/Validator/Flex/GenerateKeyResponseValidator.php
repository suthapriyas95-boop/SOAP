<?php

namespace CyberSource\SecureAcceptance\Gateway\Validator\Flex;

use CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use CyberSource\SecureAcceptance\Model\Jwk\ConverterInterface;

class GenerateKeyResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface
     */
    private $jwtProcessor;

    /**
     * @var Curl
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConverterInterface
     */
    private $jwkConverter;

    /**
     * @var bool
     */
    private $isProduction;
     /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;


    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     * @param \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface $jwtProcessor
     * @param Curl $httpClient
     * @param LoggerInterface $logger
     * @param ConverterInterface $jwkConverter
     * @param bool $isProduction
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface $jwtProcessor,
        SubjectReader $subjectReader,
        Curl $httpClient,
        LoggerInterface $logger,
        ConverterInterface $jwkConverter,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        bool $isProduction = false
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->jwtProcessor = $jwtProcessor;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->jwkConverter = $jwkConverter;
        $this->config = $config;
        $this->isProduction = $isProduction;
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = $this->subjectReader->readResponse($validationSubject);
        if (isset($response['response'])) {
            if (is_string($response['response'])) {
                $jwt = $response['response'];
            } elseif (is_array($response['response'])) {
                $jwt = null;
            }
        }

        if (!$jwt) {
            return $this->createResult(false, [__('Capture context is empty.')]);
        }

        // Decode JWT header to get key ID (kid)
        $header = $this->decodeJwtHeader($jwt);
        if ($header === false) {
            return $this->createResult(false, [__('Invalid JWT format.')]);
        }

        $kid = $header['kid'] ?? null;
        if (!$kid) {
            return $this->createResult(false, [__('Key ID is missing in the JWT header.')]);
        }

        // Fetch the public key based on key ID
        $publicKey = $this->fetchPublicKey($kid);
        if ($publicKey === false) {
            return $this->createResult(false, [__('Failed to fetch the public key.')]);
        }
        $this->logger->debug('Public Key: ' . print_r($publicKey, true));
        
        // Convert JWK to PEM format
        $pemKey = $this->jwkConverter->jwkToPem($publicKey);

        // Verify JWT signature with fetched public key
        $isValid = $this->jwtProcessor->verifySignature($jwt, $pemKey);
        $this->logger->debug('JWT signature is valid: ' . ($isValid ? 'true' : 'false'));

        return $this->createResult(
            $isValid,
            $isValid ? [] : ['Invalid microform token signature.']
        );
    }

    /**
     * Decode JWT header
     *
     * @param string $jwt
     * @return array|false
     */
    private function decodeJwtHeader($jwt)
    {
        $jwtParts = explode('.', $jwt);
        if (count($jwtParts) !== 3) {
            $this->logger->debug('Invalid JWT format.');
            return false;
        }

        $header = json_decode(base64_decode(strtr($jwtParts[0], '-_', '+/')), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->debug('Error decoding JWT header: ' . json_last_error_msg());
            return false;
        }

        return $header;
    }

    /**
     * Fetch public key from Cybersource
     *
     * @param string $kid
     * @return array|false
     */
    private function fetchPublicKey($kid)
    {
        $baseUrl = $this->config->isTestMode() ? 'https://apitest.cybersource.com/flex/v2/public-keys/' : 'https://api.cybersource.com/flex/v2/public-keys/';     
        $publicKeyUrl = $baseUrl . $kid;
        $publicKeyUrl = $baseUrl . $kid;

        $this->httpClient->get($publicKeyUrl);
        $status = $this->httpClient->getStatus();
        $publicKeyResponse = $this->httpClient->getBody();
        if ($status !== 200) {
            $this->logger->debug('Failed to fetch the public key. HTTP status: ' . $status);
            return false;
        }

        $publicKey = json_decode($publicKeyResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->debug('Error decoding public key response: ' . json_last_error_msg());
            return false;
        }

        return $publicKey;
    }
}