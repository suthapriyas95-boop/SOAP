<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\GooglePay\Controller\Index;


class Shipping extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \CyberSource\GooglePay\Model\AddressConverter
     */
    private $addressConverter;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Magento\Quote\Model\ShippingMethodManagementInterface
     */
    private $shippingMethodManagement;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\ShippingMethodManagement $shippingMethodManagement,
        \CyberSource\GooglePay\Model\AddressConverter $addressConverter
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->addressConverter = $addressConverter;
        $this->cartRepository = $cartRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->shippingMethodManagement = $shippingMethodManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {

        $resultJson = $this->resultJsonFactory->create();

        try {

            $quote = $this->checkoutSession->getQuote();

            if ($shippingAddressData = $this->getRequest()->getParam('shippingAddress')) {
                $quote->setShippingAddress($this->addressConverter->convertGoogleAddress($shippingAddressData));
            }

            if ($shippingMethodData = $this->getRequest()->getParam('shippingMethod', 'shipping_option_unselected')) {
                if ($shippingMethodData != 'shipping_option_unselected') {
                    list($shippingCarrier, $shippingMethod) = explode('_', $shippingMethodData ?? '');
                    $this->updateShippingMethod($quote, $shippingCarrier, $shippingMethod);
                }
            }

            if (!$quote->getShippingAddress()->getShippingMethod()) {
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $rates = $this->shippingMethodManagement->getList($quote->getId());
                $cheapestRate = $this->getCheapestRate($rates);
                $this->updateShippingMethod($quote, $cheapestRate->getCarrierCode(), $cheapestRate->getMethodCode());
            }

            $quote->collectTotals();

            $this->cartRepository->save($quote);

            $resultJson->setData(['success' => true]);
        } catch (\Exception $e) {
            $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }

        return $resultJson;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param $carrier
     * @param $method
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function updateShippingMethod(\Magento\Quote\Api\Data\CartInterface $quote, $carrier, $method)
    {

        $cartExtension = $quote->getExtensionAttributes();

        $quote->getShippingAddress()->setShippingMethod($carrier . '_' . $method)->setCollectShippingRates(true);

        if (!$cartExtension || !$cartExtension->getShippingAssignments()) {
            $this->shippingMethodManagement->set($quote->getId(), $carrier, $method);
            return;
        }
        $shippingAssignment = $cartExtension->getShippingAssignments()[0];
        // it's important to update shipping assignment before saving the quote!
        $shippingAssignment->getShipping()->setMethod($carrier . '_' . $method);

    }

    /**
     * @param \Magento\Quote\Api\Data\ShippingMethodInterface[] $rates
     *
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface
     */
    private function getCheapestRate($rates)
    {
        usort($rates, function ($a, $b) {
            if ($a->getAmount() == $b->getAmount()) {
                return 0;
            }
            return ($a->getAmount() < $b->getAmount()) ? -1 : 1;
        });

        return $rates[0];
    }

}
