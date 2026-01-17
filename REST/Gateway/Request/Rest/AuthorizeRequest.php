<?php
namespace CyberSource\Payment\Gateway\Request\Rest;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Build authorize request for CyberSource REST API
 */
class AuthorizeRequest implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentData = $buildSubject['payment_data'];
        $quote = isset($buildSubject['quote']) ? $buildSubject['quote'] : null;

        $request = [
            'clientReferenceInformation' => [
                'code' => $paymentData['quote_id'] ?? uniqid('admin_order_')
            ],
            'processingInformation' => [
                'commerceIndicator' => 'internet',
                'authorizationOptions' => [
                    'authType' => 'STANDARDAUTHORIZATION'
                ]
            ],
            'paymentInformation' => [
                'card' => [
                    'number' => $paymentData['card_data']['cc_number'],
                    'expirationMonth' => $paymentData['card_data']['cc_exp_month'],
                    'expirationYear' => $paymentData['card_data']['cc_exp_year'],
                    'securityCode' => $paymentData['card_data']['cc_cid'],
                    'type' => $this->getCardType($paymentData['card_data']['cc_type'])
                ]
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => $paymentData['amount'],
                    'currency' => $paymentData['currency']
                ],
                'billTo' => $this->getBillingAddress($quote)
            ]
        ];

        return $request;
    }

    /**
     * Get card type mapping
     */
    private function getCardType($ccType)
    {
        $types = [
            '001' => 'Visa',
            '002' => 'Mastercard',
            '003' => 'American Express',
            '004' => 'Discover'
        ];
        return $types[$ccType] ?? 'Unknown';
    }

    /**
     * Get billing address from quote
     */
    private function getBillingAddress($quote)
    {
        if (!$quote || !$quote->getBillingAddress()) {
            return [];
        }

        $billingAddress = $quote->getBillingAddress();
        return [
            'firstName' => $billingAddress->getFirstname(),
            'lastName' => $billingAddress->getLastname(),
            'address1' => $billingAddress->getStreetLine(1),
            'address2' => $billingAddress->getStreetLine(2),
            'locality' => $billingAddress->getCity(),
            'administrativeArea' => $billingAddress->getRegionCode(),
            'postalCode' => $billingAddress->getPostcode(),
            'country' => $billingAddress->getCountryId(),
            'email' => $billingAddress->getEmail()
        ];
    }
}