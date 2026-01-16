<?php
namespace CyberSource\Payment\Model\Api\Admin;

use CyberSource\Payment\Api\Admin\TokenGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use CyberSource\Payment\Gateway\Config\Config;
use CyberSource\Payment\Gateway\Request\Flex\GenerateKeyRequest;
use CyberSource\Payment\Gateway\Command\GatewayCommand;

/**
 * Generate Flex microform tokens for admin orders
 */
class AdminTokenGenerator implements TokenGeneratorInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var GatewayCommand
     */
    private $gatewayCommand;

    /**
     * @var GenerateKeyRequest
     */
    private $generateKeyRequest;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param GatewayCommand $gatewayCommand
     * @param GenerateKeyRequest $generateKeyRequest
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        StoreManagerInterface $storeManager,
        Config $config,
        GatewayCommand $gatewayCommand,
        GenerateKeyRequest $generateKeyRequest
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->gatewayCommand = $gatewayCommand;
        $this->generateKeyRequest = $generateKeyRequest;
    }

    /**
     * @inheritdoc
     */
    public function generateToken($quoteId, $storeId = null)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $store = $storeId ? $this->storeManager->getStore($storeId) : $quote->getStore();

            // Build request data
            $requestData = $this->generateKeyRequest->build([
                'quote' => $quote,
                'store' => $store
            ]);

            // Execute gateway command
            $result = $this->gatewayCommand->execute($requestData);

            return [
                'success' => true,
                'token' => $result['token'] ?? '',
                'client_library' => $result['client_library'] ?? '',
                'client_integrity' => $result['client_integrity'] ?? '',
                'place_order_url' => '/rest/V1/cybersource/admin/flex/place-order'
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to generate token: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function getTokenDetails($quoteId)
    {
        $tokenData = $this->generateToken($quoteId);
        return [
            'token' => $tokenData['token'],
            'client_library' => $tokenData['client_library'],
            'client_integrity' => $tokenData['client_integrity'],
            'place_order_url' => $tokenData['place_order_url']
        ];
    }

    /**
     * @inheritdoc
     */
    public function validateToken($token)
    {
        // Basic JWT structure validation
        $parts = explode('.', $token);
        return count($parts) === 3 && !empty($parts[0]) && !empty($parts[1]) && !empty($parts[2]);
    }
}