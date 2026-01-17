<?php
namespace CyberSource\Payment\Gateway\Request\Rest;

use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Build capture request for CyberSource REST API
 */
class CaptureRequest implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentData = $buildSubject['payment_data'];

        $request = [
            'clientReferenceInformation' => [
                'code' => $paymentData['order_id'] ?? uniqid('admin_capture_')
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => $paymentData['amount'],
                    'currency' => 'USD' // Should be dynamic based on order
                ]
            ],
            'merchantInformation' => [
                'merchantDescriptor' => [
                    'name' => 'CyberSource Admin Capture'
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