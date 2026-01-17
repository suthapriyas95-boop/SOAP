<?php
namespace CyberSource\Payment\Api\Admin\Vault;

use Magento\Framework\Exception\LocalizedException;

/**
 * Manage vault tokens for admin orders
 */
interface TokenManagerInterface
{
    /**
     * Save payment token after successful transaction
     *
     * @param array $paymentData
     * @param array $transactionData
     * @return string Public hash of saved token
     * @throws LocalizedException
     */
    public function saveToken(array $paymentData, array $transactionData);

    /**
     * Get customer tokens
     *
     * @param int $customerId
     * @return array List of tokens
     */
    public function getCustomerTokens($customerId);

    /**
     * Delete token
     *
     * @param string $publicHash
     * @return bool
     * @throws LocalizedException
     */
    public function deleteToken($publicHash);

    /**
     * Validate token for customer
     *
     * @param string $publicHash
     * @param int $customerId
     * @return bool
     */
    public function validateTokenForCustomer($publicHash, $customerId);
}