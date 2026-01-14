<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

class TokenDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Framework\Math\Random $random
    ) {
        $this->subjectReader = $subjectReader;
        $this->random = $random;
    }

    /**
     * Builds JTW token data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $amount = $this->subjectReader->readAmount($buildSubject);

        $result = [
            'OrderDetails' => [
                'OrderNumber' => $order->getOrderIncrementId() ?? $this->random->getUniqueHash('order_'),
                'Amount' => round($amount * 100),
                'CurrencyCode' => $order->getCurrencyCode(),
                'OrderChannel' => 'S',
            ]
        ];

        $billingAddress = $order->getBillingAddress();

        $result['Consumer']['Email1'] = $billingAddress->getEmail();

        $result['Consumer']['BillingAddress'] = $this->buildAddress($billingAddress);

        if (!empty($buildSubject['cardBin'])) {
            $result['Consumer']['Account']['AccountNumber'] = $buildSubject['cardBin'];
        }

        if ($order->getShippingAddress()) {
            $result['Consumer']['ShippingAddress'] = $this->buildAddress($order->getShippingAddress());
        };

        return $result;
    }

    /**
     * @param \Magento\Payment\Gateway\Data\AddressAdapterInterface|Magento\Sales\Model\Order\Address $address
     * @return array
     */
    private function buildAddress($address)
    {
        $addressFields = [
            'FirstName' => $address->getFirstname(),
            'LastName' => $address->getLastname(),
            'City' => $address->getCity(),
            'State' => $address->getRegionCode(),
            'CountryCode' => $address->getCountryId(),
            'Phone1' => $address->getTelephone(),
            'PostalCode' => $address->getPostcode(),
        ];

        if($address instanceof \Magento\Payment\Gateway\Data\AddressAdapterInterface) {
            $addressFields['Address1'] = $address->getStreetLine1();
            $addressFields['Address2'] = $address->getStreetLine2();
        }
        else{
            $addressFields['Address1'] = $address->getStreetLine(1);
            $addressFields['Address2'] = $address->getStreetLine(2);
        }
        return $addressFields;
    }
}
