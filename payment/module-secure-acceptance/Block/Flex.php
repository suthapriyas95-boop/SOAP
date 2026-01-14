<?php

namespace CyberSource\SecureAcceptance\Block;

use Magento\Framework\View\Element\Template;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\SecureAcceptance\Service\TokenService;
use Magento\Checkout\Model\Session as CheckoutSession;

class Flex extends \Magento\Framework\View\Element\Template
{
    private $config;
    private $tokenService;
    private $checkoutSession;

    public function __construct(
        Template\Context $context,
        Config $config,
        TokenService $tokenService,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->tokenService = $tokenService;
        $this->checkoutSession = $checkoutSession;
    }

        /**
         * Check if the current mode is sandbox (test mode)
         *
         * @return bool
         */
        public function isSandbox(): bool
        {
        return (bool) $this->config->isTestMode();
        }


    public function getClientLibrary()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->getId()) {
            $this->_logger->warning('Quote is not available or invalid.');
            return ''; // Return empty string to avoid null issues.
        }

        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibrary = $extension ? $extension->getClientLibrary() : null;
        $this->_logger->info('Retrieved clientLibrary: ' . ($clientLibrary ?? 'NULL'));
        return $clientLibrary ?? '';
    }

    public function getProductionClientLibrary()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->getId()) {
            $this->_logger->warning('Quote is not available or invalid.');
            return ''; // Return empty string to avoid null issues.
        }

        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibraryProd = $extension ? $extension->getClientLibrary() : null;
        $this->_logger->info('Retrieved production clientLibrary: ' . ($clientLibraryProd ?? 'NULL'));
        return $clientLibraryProd ?? '';
    }

    public function getClientLibraryIntegrity()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->getId()) {
            $this->_logger->warning('Quote is not available or invalid.');
            return ''; // Return empty string to avoid null issues.
        }

        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibraryIntegrity = $extension ? $extension->getClientLibraryIntegrity() : null;
        $this->_logger->info('Retrieved clientLibraryIntegrity: ' . ($clientLibraryIntegrity ?? 'NULL'));
        return $clientLibraryIntegrity ?? '';
    }
}
 