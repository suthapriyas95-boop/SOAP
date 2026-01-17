<?php
namespace CyberSource\Payment\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Build SOP (Secure Order Post) request data for admin orders
 */
interface SopRequestDataBuilderInterface
{
    /**
     * Build SOP request data
     *
     * @param int $quoteId
     * @param string $cardType Card type code (001, 002, etc.)
     * @param bool $vaultEnabled
     * @param int|null $storeId
     * @return array Request fields with signature
     * @throws LocalizedException
     */
    public function buildRequestData(
        $quoteId,
        $cardType,
        $vaultEnabled = false,
        $storeId = null
    );

    /**
     * Validate request data
     *
     * @param array $data
     * @return bool
     * @throws LocalizedException
     */
    public function validateRequestData(array $data);

    /**
     * Get form fields
     *
     * @param int $quoteId
     * @return array Form fields
     */
    public function getFormFields($quoteId);
}