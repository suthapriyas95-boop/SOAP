<?php
namespace CyberSource\Payment\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Generate Flex microform tokens for admin orders
 */
interface TokenGeneratorInterface
{
    /**
     * Generate a Flex microform token for admin quote
     *
     * @param int $quoteId
     * @param int|null $storeId
     * @return array Token data with client library info
     * @throws LocalizedException
     */
    public function generateToken($quoteId, $storeId = null);

    /**
     * Get token details and library information
     *
     * @param int $quoteId
     * @return array Token details with URLs
     * @throws LocalizedException
     */
    public function getTokenDetails($quoteId);

    /**
     * Validate token integrity
     *
     * @param string $token
     * @return bool
     */
    public function validateToken($token);
}