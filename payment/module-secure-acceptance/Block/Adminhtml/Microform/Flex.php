<?php

namespace CyberSource\SecureAcceptance\Block\Adminhtml\Microform;

use Magento\Framework\View\Element\Template;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\SecureAcceptance\Service\Adminhtml\TokenService;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use CyberSource\Core\Model\LoggerInterface;

class Flex extends Template
{
    private $config;
    private $tokenService;
    private $backendQuoteSession;
    private $logger;

    public function __construct(
        Template\Context $context,
        Config $config,
        TokenService $tokenService,
        BackendQuoteSession $backendQuoteSession,      
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->tokenService = $tokenService;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->_logger = $logger;
    }

    public function isSandbox()
    {
        return $this->config->isTestMode();
    }

    public function getClientIntegrity()
    {
        $quote =  $this->backendQuoteSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return null;
        }

        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibraryIntegrity = $extension ? $extension->getClientLibraryIntegrity() : null;
        $this->_logger->info('Retrieved clientLibraryIntegrity: ' . ($clientLibraryIntegrity ?? 'NULL'));
        return $clientLibraryIntegrity ?? '';
    }


    public function getClientLibrary()
    {
        $quote =  $this->backendQuoteSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return null;
        }
        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibrary = $extension ? $extension->getClientLibrary() : null;
        $this->_logger->info('Retrieved clientLibrary: ' . ($clientLibrary ?? 'NULL'));
        return $clientLibrary ?? '';
    }
    

    public function getProductionClientLibrary()
    {
        $quote =  $this->backendQuoteSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return null;
        }
         
        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibraryProd = $extension ? $extension->getClientLibrary() : null;
        $this->_logger->info('Retrieved production clientLibrary: ' . ($clientLibraryProd ?? 'NULL'));
        return $clientLibraryProd ?? '';
    }
}