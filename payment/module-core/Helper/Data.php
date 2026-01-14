<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Helper;

use Magento\Sales\Api\OrderRepositoryInterface;

class Data extends AbstractDataBuilder
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry = null;

    /**
     * Order
     *
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    private $order = null;

    /**
     * Url Builder
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Order Factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \CyberSource\Core\Model\Config
     */
    private $gatewayConfig;

    /**
     * @var array
     */
    private $includeAdditionalPaymentKeys = [
        'reasonCode',
        'requestID',
        'request_id',
        'last4',
        'cardType',
        'merchantReferenceCode',
        'merchant_reference_number',
        'total_tax_amount',
        'sa_type',
        'method_name',
        'authorize',
        'capture'
    ];

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Helper\Data $data
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Sales\Model\AdminOrder\Create $order
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \CyberSource\Core\Model\Config $config
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\GiftMessage\Model\Message $giftMessage
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param array $additionalInfoKeys
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $data,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Sales\Model\AdminOrder\Create $order,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \CyberSource\Core\Model\Config $config,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory,
        \Magento\Backend\Model\Auth $auth,
        \Magento\GiftMessage\Model\Message $giftMessage,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        $additionalInfoKeys = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $data,
            $orderCollectionFactory,
            $orderGridCollectionFactory,
            $auth,
            $giftMessage
        );

        $this->orderRepository = $orderRepository;
        $this->coreRegistry = $coreRegistry;
        $this->order = $order;
        $this->urlBuilder = $backendUrl;
        $this->orderFactory = $orderFactory;
        $this->gatewayConfig = $config;
        $this->serializer = $serializer;

        $this->includeAdditionalPaymentKeys = array_merge($this->includeAdditionalPaymentKeys, array_values($additionalInfoKeys));
    }

    public function getTokens($storeId)
    {
        return [];
    }

    public function getGatewayConfig()
    {
        return $this->gatewayConfig;
    }

    /**
     * Return quote for admin order
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->order->getQuote();
    }

    /**
     * Retrieve place order url in admin
     *
     * @return  string
     */
    public function getPlaceOrderAdminUrl()
    {
        return $this->_getUrl('cybersourceadmin/order_cybersource/payment', []);
    }

    /**
     * Return only visible info from CyberSource Response
     *
     * @param $request
     * @return array
     */
    public function getAdditionalData($request)
    {
        $keys = array_keys($request);

        $visibleData = [];
        foreach ($keys as $key) {
            if (in_array($key, $this->includeAdditionalPaymentKeys) ||
                preg_match('/^Tax Amount for/', $key)) {
                switch ($key) {
                    case 'sa_type':
                        $visibleData['Method'] = ($request[$key] == 'web') ? 'Secure Acceptance WEB/Mobile' : 'Secure Acceptance SOP';
                        break;
                    case 'cardType':
                        $visibleData[$key] = $this->getCardType($request[$key]);
                        break;

                    case 'capture':
                        $paypalCaptureResponse = $this->serializer->unserialize($request[$key]);
                        foreach ((array)$paypalCaptureResponse->apCaptureReply as $key => $value) {
                            $visibleData[$key] = $value;
                        }
                        break;

                    case 'authorize':
                        $authorizeResponse = (object) $this->serializer->unserialize($request[$key]);

                        foreach ((array)$authorizeResponse->apAuthReply as $key => $value) {
                            $visibleData[$key] = $value;
                        }
                        break;

                    default:
                        $visibleData[$key] = $request[$key];
                }
            }
        }

        return $visibleData;
    }
    
    private function getCardType($code)
    {
        $types = [
            '001' => 'VI',
            '002' => 'MC',
            '003' => 'AE',
            '004' => 'DI',
            '005' => 'DN',
            '007' => 'JCB',
            '042' => 'MI'
        ];
        return (!empty($types[$code])) ? $types[$code] : $code;
    }
}
