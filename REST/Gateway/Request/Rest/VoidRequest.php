<?php
namespace CyberSource\Payment\Gateway\Request\Rest;

use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Build void request for CyberSource REST API
 */
class VoidRequest implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentData = $buildSubject['payment_data'];

        $request = [
            'clientReferenceInformation' => [
                'code' => $paymentData['order_id'] ?? uniqid('admin_void_')
            ],
            'reversalInformation' => [
                'amountDetails' => [
                    'totalAmount' => '0.00' // Void full amount
                ],
                'reason' => '34' // Void reason code
            ]
        ];

        // Add transaction reference if available
        if (isset($paymentData['transaction_id'])) {
            $request['clientReferenceInformation']['transactionId'] = $paymentData['transaction_id'];
        }

        return $request;
    }
}