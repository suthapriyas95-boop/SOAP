<?php
namespace CyberSource\Payment\Model\Api\Admin;

use CyberSource\Payment\Api\Admin\SopRequestDataBuilderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use CyberSource\Payment\Gateway\Config\Config;
use CyberSource\Payment\Helper\RequestDataBuilder;

/**
 * Build SOP request data for admin orders
 */
class AdminSopRequestDataBuilder implements SopRequestDataBuilderInterface
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
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param RequestDataBuilder $requestDataBuilder
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        StoreManagerInterface $storeManager,
        Config $config,
        RequestDataBuilder $requestDataBuilder
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->requestDataBuilder = $requestDataBuilder;
    }

    /**
     * @inheritdoc
     */
    public function buildRequestData(
        $quoteId,
        $cardType,
        $vaultEnabled = false,
        $storeId = null
    ) {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $store = $storeId ? $this->storeManager->getStore($storeId) : $quote->getStore();

            // Build SOP request data
            $requestData = $this->requestDataBuilder->buildSilentRequestData(
                $quote,
                $store,
                $cardType,
                $vaultEnabled
            );

            return [
                'success' => true,
                'fields' => $requestData
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to build request data: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateRequestData(array $data)
    {
        $required = ['quote_id', 'cc_type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new LocalizedException(__('Required field missing: %1', $field));
            }
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getFormFields($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId);
        $store = $quote->getStore();

        return $this->requestDataBuilder->buildSilentRequestData(
            $quote,
            $store,
            null,
            false
        );
    }
}