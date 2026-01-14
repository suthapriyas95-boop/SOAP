<?php

namespace CyberSource\SecureAcceptance\Gateway\Validator\Flex;

use CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use CyberSource\SecureAcceptance\Model\Jwk\ConverterInterface;

class MicroformResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var bool
     */
    private $isAdminHtml;

    /**
     * @var SignatureValidator\ValidatorInterface
     */
    private $signatureValidator;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface
     */
    private $jwtProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Curl
     */
    private $httpClient;

    /**
     * @var ConverterInterface
     */
    private $jwkConverter;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param \CyberSource\SecureAcceptance\Gateway\Config\Config $config
     * @param SubjectReader $subjectReader
     * @param \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface $jwtProcessor
     * @param \CyberSource\SecureAcceptance\Gateway\Validator\Flex\SignatureValidator\ValidatorInterface $signatureValidator
     * @param LoggerInterface $logger
     * @param Curl $httpClient
     * @param ConverterInterface $jwkConverter
     * @param bool $isAdminHtml
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface $jwtProcessor,
        \CyberSource\SecureAcceptance\Gateway\Validator\Flex\SignatureValidator\ValidatorInterface $signatureValidator,
        LoggerInterface $logger,
        Curl $httpClient,
        ConverterInterface $jwkConverter,
        $isAdminHtml = false,
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->isAdminHtml = $isAdminHtml;
        $this->signatureValidator = $signatureValidator;
        $this->jwtProcessor = $jwtProcessor;
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->jwkConverter = $jwkConverter;
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $payment = $validationSubject['payment'] ?? null;

        if (!$payment) {
            return $this->createResult(false, ['Payment must be provided.']);
        }

        if ($payment->getMethod() != \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE) {
            return $this->createResult(true);
        }

        if (!$this->config->isMicroform() || $this->isAdminHtml) {
            return $this->createResult(true);
        }

        if ($payment instanceof \Magento\Quote\Model\Quote\Payment) {
            return $this->createResult(true);
        }

        $jwtCapcontext = $payment->getAdditionalInformation('captureContext');
        $jwt = $payment->getAdditionalInformation('flexJwt');
        $microformPublicKey = $payment->getAdditionalInformation('microformPublicKey');
    
        // Decode JWT header to get key ID (kid)
        $header = $this->decodeJwtHeader($jwtCapcontext);
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
        // Convert JWK to PEM format
        $pemKey = $this->jwkConverter->jwkToPem($publicKey);

        // Verify JWT signature with fetched public key
        $isValidJWT = $this->jwtProcessor->verifySignature($jwtCapcontext, $pemKey);
        $isValid = $this->jwtProcessor->verifySignature($jwt, $microformPublicKey);
        $this->logger->debug('JWT signature is valid: ' . ($isValid ? 'true' : 'false'));

        return $this->createResult(
            $isValid,
            $isValid ? [] : ['Invalid token signature.']
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