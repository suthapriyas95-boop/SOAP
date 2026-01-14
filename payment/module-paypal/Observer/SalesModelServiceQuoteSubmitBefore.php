<?php

namespace CyberSource\PayPal\Observer;

use CyberSource\PayPal\Model\Express\Checkout;
use Magento\Framework\Event\Observer as EventObserver;

use Magento\Sales\Model\Order;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;

class SalesModelServiceQuoteSubmitBefore implements ObserverInterface
{
    /**
     * @var PaymentTokenInterfaceFactory
     */
    private $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
     */
    public function __construct(
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
    ) {
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->paymentTokenFactory = $paymentTokenFactory;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment();
        if (! $payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_BA_ID)) {
            return;
        }

        $extensionAttributes = $this->getExtensionAttributes($payment);
        $extensionAttributes->setVaultPaymentToken(
            $this->getVaultPaymentToken($payment)
        );
    }

    /**
     * @param InfoInterface $payment
     * @return PaymentTokenInterface
     */
    private function getVaultPaymentToken($payment)
    {
        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create();

        $paymentToken->setGatewayToken($payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_BA_ID));
        $paymentToken->setType(\Magento\Vault\Model\AccountPaymentTokenFactory::TOKEN_TYPE_ACCOUNT);
        $paymentToken->setExpiresAt(strtotime('+1 year'));
        $paymentToken->setTokenDetails(json_encode(
            [
                'date' => $payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_BA_DATE),
                'email' => $payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_BA_EMAIL),
                'payerId' => $payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_BA_PAYER_ID),
                'requestId' => $payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_BA_REQUEST_ID)
            ]
        ));

        return $paymentToken;
    }

    /**
     * Get Extension Attributes
     *
     * @param InfoInterface $payment
     * @return \Magento\Framework\Api\ExtensionAttributesInterface
     */
    private function getExtensionAttributes($payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
