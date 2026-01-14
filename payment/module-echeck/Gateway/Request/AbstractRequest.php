<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Request;

use CyberSource\Core\Model\Config as CoreConfig;
use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\GiftMessage\Model\Message as GiftMessage;
use CyberSource\ECheck\Gateway\Helper\SubjectReader;

abstract class AbstractRequest
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    protected $auth;

    /**
     * @var GiftMessage
     */
    protected $giftMessage;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory
     */
    private $orderGridCollectionFactory;


    /**
     * @param Config $config
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory
     * @param \Magento\Backend\Model\Auth $auth
     * @param GiftMessage $giftMessage
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        OrderCollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory,
        \Magento\Backend\Model\Auth $auth,
        GiftMessage $giftMessage,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->remoteAddress = $remoteAddress;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->auth = $auth;
        $this->giftMessage = $giftMessage;
        $this->subjectReader = $subjectReader;
        $this->orderGridCollectionFactory = $orderGridCollectionFactory;
    }

    /**
     *  Account type. Possible values:
        C : Checking
        S : Savings (U.S. dollars only)
        X : Corporate checking (U.S. dollars only)
     */
    const ACCOUNT_TYPE = "C";

    /**
     *  TeleCheck
        Accepts only the following values:
        - PPD
        - TEL
        - WEB
     */
    const SEC_CODE = "WEB";

    /**
     * @param $merchantID
     * @param $merchantReferenceCode
     * @return \stdClass
     */
    protected function buildAuthNodeRequest($merchantID, $merchantReferenceCode)
    {
        $request = new \stdClass();
        $request->merchantID = $merchantID;
        $request->partnerSolutionID = \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID;
        $this->config->setMethodCode(CoreConfig::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\ECheck\Model\Ui\ConfigProvider::CODE);
        $request->merchantReferenceCode = $merchantReferenceCode;

        return $request;
    }

    /**
     * @param $quoteAddress
     * @return \stdClass
     */
    protected function buildAddress($quoteAddress, $addressType = null, $payment = null)
    {
        $address = new \stdClass();
        $address->city =  $quoteAddress->getCity();
        $address->country = $quoteAddress->getCountryId();
        $address->postalCode = $quoteAddress->getPostcode();
        $address->state = $quoteAddress->getRegionCode();
        $address->email = $quoteAddress->getEmail();
        $address->company = $quoteAddress->getCompany();
        $address->firstName = $quoteAddress->getFirstname();
        $address->lastName = $quoteAddress->getLastname();
        $address->phoneNumber = $quoteAddress->getTelephone();

        if($quoteAddress instanceof \Magento\Payment\Gateway\Data\AddressAdapterInterface)
        {
            $address->street1 = $quoteAddress->getStreetLine1();
            $address->street2 = $quoteAddress->getStreetLine2();
        }
        else{
            $address->street1 = $quoteAddress->getStreetLine(1);
            $address->street2 = $quoteAddress->getStreetLine(2);
        }
		
        if ($payment && $driversLicenseNnumber = $payment->getAdditionalInformation('drivers_license_number')
            && $driversLicenseState = $payment->getAdditionalInformation('drivers_license_state')) {
            $address->driversLicenseNumber = $driversLicenseNnumber;
            $address->driversLicenseState = $driversLicenseState;
        }

        if ($addressType == \Magento\Quote\Model\Quote\Address::TYPE_BILLING) {
            $address->ipAddress = $this->remoteAddress->getRemoteAddress();
        }

        return $address;
    }

    /**
     * @param $bankTransitNumber
     * @param $accountNumber
     * @return \stdClass
     */
    protected function buildAccountNode($bankTransitNumber, $accountNumber, $checkNumber = null)
    {
        $check = new \stdClass();
        $check->accountNumber = (string) $accountNumber;
        $check->accountType = self::ACCOUNT_TYPE;
        $check->bankTransitNumber = (string) $bankTransitNumber;
        if ($checkNumber) {
            $check->checkNumber = (string)$checkNumber;
        }

        $check->secCode = $this->config->getSecCode() ?: self::SEC_CODE;

        return $check;
    }

    /**
     * @param array $items
     * @param \stdClass $request
     * @return mixed
     */
    protected function buildRequestItems(array $items, \stdClass $request)
    {
        $isBundle = false;
        foreach ($items as $i => $item) {

            if (empty($item->getPrice()) && $item->getParentItemId()) {
                continue;
            }

            $qty = $item->getQtyOrdered();
            if (empty($qty)) {
                $qty = 1;
            }

            $amount = ($item->getPrice() - ($item->getDiscountAmount() / $qty));
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $qty;
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->formatAmount($amount);
            $requestItem->taxAmount = $this->formatAmount($item->getTaxAmount());
            $requestItem->parentId = $item->getParentItemId();

            $request->item[] = $requestItem;

            if ($item->getProductType() === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $isBundle = true;
                $i++;
                foreach ($item->getQtyOptions() as $option) {
                    $product = $option->getProduct();
                    $requestItem = new \stdClass();
                    $requestItem->id = $i++;
                    $requestItem->productName = $product->getName();
                    $requestItem->productSKU = $product->getSku();
                    $requestItem->quantity = $product->getQuoteItemQty();
                    $requestItem->productCode = 'default';
                    $requestItem->unitPrice = $this->formatAmount(0);
                    $requestItem->taxAmount = $this->formatAmount(0);

                    $request->item[] = $requestItem;
                }
            }
        }

        $shippingCost = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingInclTax();
        $shippingCostItem = new \stdClass();
        $shippingCostItem->id = count($request->item) + 1;
        $shippingCostItem->productCode = 'shipping_and_handling';
        $shippingCostItem->unitPrice = $this->formatAmount($shippingCost);
        $shippingCostItem->parentId = null;
        $request->item[] = $shippingCostItem;

        if (property_exists($request, 'item') && is_array($request->item) && !$isBundle) {
            foreach ($request->item as $key => $item) {
                if ($item->unitPrice == 0 && $item->parentId !== null && $item->productCode != 'shipping_and_handling') {
                    unset($request->item[$key]);
                }
            }

            $request->item = array_values($request->item);
        }

        foreach ($request->item as $key => $item) {
            if (property_exists($item, 'parentId')) {
                unset($request->item[$key]->parentId);
            }
        }

        return $request;
    }

    /**
     * @param float $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return sprintf('%.2F', $amount);
    }

    /**
     *
     * @param $payment
     * @return \stdClass
     */
    protected function buildDecisionManagerFields($payment)
    {
        $merchantDefinedData = new \stdClass;
        $merchantDefinedData->field1 = (int) $this->customerSession->isLoggedIn(); // Registered or Guest Account

        if ($this->customerSession->isLoggedIn()) {
            $merchantDefinedData->field2 = $this->customerSession->getCustomerData()->getCreatedAt(); // Account Creation Date

            $orders = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
                ->setOrder('created_at', 'desc');

            $merchantDefinedData->field3 = count($orders); // Purchase History Count

            if ($orders->getSize() > 0) {
                $lastOrder = $orders->getFirstItem();
                $merchantDefinedData->field4 = $lastOrder->getData('created_at'); // Last Order Date
            }

            $merchantDefinedData->field5 = round((time() - strtotime($this->customerSession->getCustomerData()->getCreatedAt() ?? '')) / (3600*24));// Member Account Age (Days)
        }

        $orders = $this->orderGridCollectionFactory->create()
            ->addFieldToFilter('customer_email', $payment->getCustomerEmail())
        ;
        $orders->getSelect()->limit(1);

        $merchantDefinedData->field6 = (int)(count($orders) > 0); // Repeat Customer
        $merchantDefinedData->field20 = $payment->getCouponCode(); //Coupon Code
        $merchantDefinedData->field21 = ($payment->getSubtotal() - $payment->getSubtotalWithDiscount()); // Discount

        $message = $this->giftMessage->load($payment->getGiftMessageId());
        $merchantDefinedData->field22 = ($message) ? $message->getMessage() : ''; // Gift Message
        $merchantDefinedData->field23 = ($this->auth->isLoggedIn()) ? 'call center' : 'web'; //order source

        return $merchantDefinedData;
    }
}
