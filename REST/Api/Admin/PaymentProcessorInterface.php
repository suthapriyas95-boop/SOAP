<?php
namespace CyberSource\Payment\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Process CyberSource payments for admin orders
 */
interface PaymentProcessorInterface
{
    /**
     * Authorize payment only
     *
     * @param array $paymentData
     * @return array Authorization result
     * @throws LocalizedException
     */
    public function authorize(array $paymentData);

    /**
     * Authorize and capture payment
     *
     * @param array $paymentData
     * @return array Sale result
     * @throws LocalizedException
     */
    public function sale(array $paymentData);

    /**
     * Capture authorized payment
     *
     * @param array $paymentData
     * @return array Capture result
     * @throws LocalizedException
     */
    public function capture(array $paymentData);

    /**
     * Void authorized transaction
     *
     * @param array $paymentData
     * @return array Void result
     * @throws LocalizedException
     */
    public function void(array $paymentData);

    /**
     * Refund captured payment
     *
     * @param array $paymentData
     * @return array Refund result
     * @throws LocalizedException
     */
    public function refund(array $paymentData);
}