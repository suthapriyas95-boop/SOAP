<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Controller\Index;

class Request extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $session;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Store\Model\Information
     */
    private $storeInfo;

    /**
     * @var \CyberSource\ApplePay\Gateway\Config\Config
     */
    private $config;
    /**
     * @var \Magento\Quote\Model\ShippingMethodManagementInterface
     */
    private $shippingMethodManagement;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;

    /**
     * Request constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \CyberSource\ApplePay\Gateway\Config\Config $config
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Store\Model\Information $storeInfo
     * @param \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagement
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CyberSource\ApplePay\Gateway\Config\Config $config,
        \Magento\Checkout\Model\Session $session,
        \Magento\Store\Model\Information $storeInfo,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagement,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->storeInfo = $storeInfo;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->pricingHelper = $pricingHelper;

        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $resultJson = $this->resultJsonFactory->create();

        try {
            $quote = $this->session->getQuote();

            $shippingAddress = $quote->getShippingAddress();
            $data = [
                'success' => true,
                'request' => [
                    'total' => [
                        'currencyCode' => $quote->getCurrency()->getBaseCurrencyCode(),
                        'totalPrice' => sprintf('%.2F', $quote->getBaseGrandTotal()),
                        'totalPriceStatus' => $quote->isVirtual() || $shippingAddress && $shippingAddress->getShippingMethod() ? 'FINAL' : 'ESTIMATED',
                    ]
                ]
            ];

            if (!$quote->isVirtual() && $shippingAddress && $shippingAddress->getCountryId()) {
                $data['request']['rates'] = $this->getShippingRates($quote);
                if ($shippingAddress->getShippingMethod()) {
                    $data['request']['defaultSelectedOptionId'] = $shippingAddress->getShippingMethod();
                }
            }

            $resultJson->setData($data);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $resultJson->setData([
                'success' => false,
                'message' => __('Unable to get cart details.'),
            ]);
        }

        return $resultJson;
    }

    public function getShippingRates($quote)
    {
        $result = [];

        $rates = $this->shippingMethodManagement->getList($quote->getId());

        foreach ($rates as $rate) {
            $result[] = [
                'id' => $rate->getCarrierCode() . '_' . $rate->getMethodCode(),
                'label' => $this->pricingHelper->currency($rate->getBaseAmount(), true, false)
                    . ': '
                    . $rate->getMethodTitle(),
                'description' => $rate->getCarrierTitle() . ' ' . $rate->getMethodTitle(),
            ];
        }

        return $result;
    }

}
