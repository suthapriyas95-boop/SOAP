<?php
namespace CyberSource\Payment\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Handle SOP (Secure Order Post) responses for admin orders
 */
interface SopResponseHandlerInterface
{
    /**
     * Handle CyberSource SOP response and create order
     *
     * @param array $response CyberSource response data
     * @param array $orderData Order creation data
     * @return array Order creation result
     * @throws LocalizedException
     */
    public function handleResponse(array $response, array $orderData);

    /**
     * Validate CyberSource response signature
     *
     * @param array $response
     * @return bool
     * @throws LocalizedException
     */
    public function validateSignature(array $response);

    /**
     * Process response data and create order
     *
     * @param array $response
     * @return array Order data
     */
    public function processResponse(array $response);
}