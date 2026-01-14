<?php

namespace CyberSource\SecureAcceptance\Service\Adminhtml;

use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Framework\Url\DecoderInterface;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use Magento\Quote\Model\QuoteRepository;
use CyberSource\Core\Model\LoggerInterface;
use CyberSource\SecureAcceptance\Gateway\Config\Config;

class TokenService
{
    const COMMAND_CODE = 'generate_flex_key';

    private $commandManager;
    private $urlDecoder;
    private $backendQuoteSession;
    private $quoteRepository;
    private $logger;
    private $config;

    public function __construct(
        CommandManagerInterface $commandManager,
        DecoderInterface $urlDecoder,
        BackendQuoteSession $backendQuoteSession,
        QuoteRepository $quoteRepository,
        LoggerInterface $logger,
        Config $config
    ) {
        $this->commandManager = $commandManager;
        $this->urlDecoder = $urlDecoder;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function generateToken()
    {
        $quote =  $this->backendQuoteSession->getQuote();

        if (!$quote || !$quote->getId()) {
                $this->logger->warning('Cart is empty or unable to load cart data.');
                return; // Exit if the cart is empty or the quote is invalid
        }

        if ($this->config->isMicroform()) {
            $commandResult = $this->commandManager->executeByCode(
                self::COMMAND_CODE,
                $quote->getPayment()
            );

            // Check if $commandResult is an object and has a get() method.
            if (is_object($commandResult) && method_exists($commandResult, 'get')) {
                $commandData = $commandResult->get();
            } else {
                $commandData = $commandResult;
            }
            $this->quoteRepository->save($quote);

            // Ensure the expected 'response' key exists
            if (!isset($commandData['response'])) {
                return ['error' => __('Invalid response from token generation.')];
            }

            $captureContextValue = $commandData['response'];

            // Decode the capture context value safely
            $decodedCaptureResponse = json_decode($this->urlDecoder->decode(explode('.', $captureContextValue)[1]));
            $ctxData = $decodedCaptureResponse->ctx[0]->data ?? null;

            if ($ctxData) {
                $quoteExtension = $quote->getExtensionAttributes();
                if (!$quoteExtension) {
                    $quoteExtension = $this->quoteRepository->create();
                }
                // Set clientLibraryIntegrity if not already set
                if (!$quoteExtension->getClientLibraryIntegrity()) {
                    $quoteExtension->setClientLibraryIntegrity($ctxData->clientLibraryIntegrity ?? null);
                }
                // Set clientLibrary if not already set
                if (!$quoteExtension->getClientLibrary()) {
                    $quoteExtension->setClientLibrary($ctxData->clientLibrary ?? null);
                }

                $quote->setExtensionAttributes($quoteExtension);
                $this->quoteRepository->save($quote);
            }
        } else {
            return true;
        }
    }
}