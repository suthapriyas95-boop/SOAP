<?php

namespace CyberSource\SecureAcceptance\Controller\Microform;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use CyberSource\SecureAcceptance\Service\TokenService;
use CyberSource\Core\Model\LoggerInterface;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\Core\Model\Source\SecureAcceptance\Type;
use Magento\Framework\Filesystem;

class ValidateToken extends Action
{
    protected $resultJsonFactory;
    protected $checkoutSession;
    protected $tokenService;
    protected $logger;
    protected $scopeConfig;
    protected $fileDriver;
    protected $directoryList;
    private $filesystem;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        TokenService $tokenService,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        File $fileDriver,
        DirectoryList $directoryList,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->tokenService      = $tokenService;
        $this->logger            = $logger;
        $this->scopeConfig       = $scopeConfig;
        $this->fileDriver        = $fileDriver;
        $this->directoryList     = $directoryList;
        $this->filesystem        = $filesystem;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            // Initialize variables to prevent undefined errors
            $clientLibrary = '';
            $clientLibraryIntegrity = '';

            // Ensure a valid checkout quote exists.
            $quote = $this->checkoutSession->getQuote();
            if (!$quote || !$quote->getId()) {
                throw new LocalizedException(__('Quote is invalid or empty.'));
            }

            // Retrieve Payment API mode and Checkout Flow Type from configuration.
            $paymentApiMode = $this->scopeConfig->getValue(
                'payment/chcybersource/sa_flow_mode',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $checkoutFlowType = $this->scopeConfig->getValue(
                'payment/chcybersource/secureacceptance_type',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // Only perform P12 certificate validation in SOAP flow mode for specific checkout types.
            if (
                $paymentApiMode == Config::SOAP_FLOW &&
                in_array($checkoutFlowType, [Type::SA_WEB, Type::SA_SOP, Type::SA_FLEX_MICROFORM], true)
            ) {
                // Retrieve P12 certificate and access key (password) from configuration.
                $p12AccessKey = $this->scopeConfig->getValue(
                    'payment/chcybersource/p12_accesskey',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );

                $p12Certificate = $this->scopeConfig->getValue(
                    'payment/chcybersource/general_p12_certificate',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );

                // Throw error if either the access key or certificate filename is missing.
                if (empty($p12AccessKey) || empty($p12Certificate)) {
                    throw new LocalizedException(__('Something went wrong due to unable to build token.'));
                }

                $fullPath = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->getAbsolutePath('certificates/' . $p12Certificate);
                
                // Verify that the file exists.
                if (!$this->fileDriver->isExists($fullPath)) {
                    throw new LocalizedException(__('Something went wrong due to unable to build token.'));
                }

                $certificateContent = $this->fileDriver->fileGetContents($fullPath);

                // Attempt to decrypt (read) the PKCS#12 certificate using the provided access key.
                if (!openssl_pkcs12_read($certificateContent, $certData, $p12AccessKey)) {
                    throw new LocalizedException(__('Something went wrong due to unable to build token.'));
                }

                // Generate token
                $this->tokenService->generateToken();

                // Retrieve extension attributes
                $extension = $quote->getExtensionAttributes();
                $clientLibrary = $extension ? $extension->getClientLibrary() : '';
                $clientLibraryIntegrity = $extension ? $extension->getClientLibraryIntegrity() : '';

                // Validate token attributes **only for Type::SA_FLEX_MICROFORM**
                if ($checkoutFlowType === Type::SA_FLEX_MICROFORM && (empty($clientLibrary) || empty($clientLibraryIntegrity))) {
                    throw new LocalizedException(__('Something went wrong due to unable to build token.'));
                }
            }

            // Return the successful response with token attributes
            $result->setData([
                'success' => true,
                'checkoutFlowType' => $checkoutFlowType,
                'clientLibrary' => $clientLibrary,
                'clientLibraryIntegrity' => $clientLibraryIntegrity
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Token Validation Error: ' . $e->getMessage());
            $result->setData([
                'error'     => true,
                'error_msg' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
