<?php
namespace CyberSource\Payment\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Create orders using Flex microform tokens for admin
 */
interface FlexOrderCreatorInterface
{
    /**
     * Create order from Flex token data
     *
     * @param int $quoteId
     * @param string $token JWT token from Flex
     * @param array $cardData Card information
     * @param array $orderData Additional order data
     * @return array Order creation result
     * @throws LocalizedException
     */
    public function createOrder(
        $quoteId,
        $token,
        array $cardData,
        array $orderData
    );

    /**
     * Validate Flex token and card data
     *
     * @param string $token
     * @param array $cardData
     * @return bool
     * @throws LocalizedException
     */
    public function validateTokenData($token, array $cardData);

    /**
     * Process payment with Flex token
     *
     * @param int $quoteId
     * @param string $token
     * @return array Payment result
     */
    public function processPayment($quoteId, $token);
}