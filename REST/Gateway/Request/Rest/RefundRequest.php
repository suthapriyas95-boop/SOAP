<?php
namespace CyberSource\Payment\Gateway\Request\Rest;

use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Build refund request for CyberSource REST API
 */
class RefundRequest implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentData = $buildSubject['payment_data'];

        $request = [
            'clientReferenceInformation' => [
                'code' => $paymentData['order_id'] ?? uniqid('admin_refund_')
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => $paymentData['amount'],
                    'currency' => 'USD' // Should be dynamic based on order
                ]
            ]
        ];

        // Add transaction reference if available
        if (isset($paymentData['transaction_id'])) {
            $request['clientReferenceInformation']['transactionId'] = $paymentData['transaction_id'];
        }

        return $request;
    }
}