<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Service;

use CyberSource\Core\Helper\RequestDataBuilder;
use CyberSource\Core\Model\Config;
use CyberSource\Core\Service\AbstractConnection;
use CyberSource\Tax\Model\Config as TaxConfig;
use Magento\Framework\App\ProductMetadata;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CyberSourceSoapAPI extends AbstractConnection implements \CyberSource\Tax\Service\TaxServiceInterface
{
    const SUCCESS_REASON_CODE = 100;

    /**
     * @var \SoapClient
     */
    public $client;

    /**
     * @var RequestDataBuilder
     */
    private $requestDataHelper;

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var TaxConfig
     */
    private $taxConfig;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    private $productMetadata;

    /**
     * @var \Magento\Tax\Helper\Data $taxData
     */
    private $taxData;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    private $taxClassRepository;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * CyberSourceSoapAPI constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $gatewayConfig
     * @param TaxConfig $taxConfig
     * @param LoggerInterface $logger
     * @param RequestDataBuilder $requestDataHelper
     * @param ProductMetadata $productMetadata
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepositoryInterface
     * @param \Magento\Framework\Math\Random $random
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \SoapClient|null $client
     *
     * @throws \Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $gatewayConfig,
        TaxConfig $taxConfig,
        LoggerInterface $logger,
        RequestDataBuilder $requestDataHelper,
        ProductMetadata $productMetadata,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepositoryInterface,
        \Magento\Framework\Math\Random $random,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        ?\SoapClient $client = null
    ) {

        if ($taxConfig->isTaxEnabled()) {
            parent::__construct($scopeConfig, $logger);
        }

        /**
         * Added soap client as parameter to be able to mock in unit tests.
         */
        if ($client !== null) {
            $this->setSoapClient($client);
        }

        $this->gatewayConfig = $gatewayConfig;
        $this->taxConfig = $taxConfig;

        $this->client = $this->getSoapClient();
        $this->requestDataHelper = $requestDataHelper;
        $this->productMetadata = $productMetadata;
        $this->taxData = $taxData;
        $this->taxClassRepository = $taxClassRepositoryInterface;
        $this->random = $random;
        $this->serializer = $serializer;
    }

    /**
     * Tax calculation for order
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
     * @param null $storeId
     *
     * @return array|null
     */
    public function getTaxForOrder(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails,
        $storeId = null
    ) {

        if (!$this->taxConfig->isTaxEnabled()) {
            return null;
        }

        $shippingAddress = $quoteTaxDetails->getShippingAddress();
        $billingAddress = $quoteTaxDetails->getBillingAddress();

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        if ($developerId = $this->gatewayConfig->getDeveloperId()) {
            $request->developerId = $developerId;
        }

        $request->merchantReferenceCode = $this->random->getUniqueHash('tax_request_');

        /**
         * Try to add the billingAddress from customer to billTo, when it's not available, use the store address
         * as billing address, since the tax is calculated based on store address (shipFrom)
         */
        $builtBillingAddress = $this->buildAddressForTax($billingAddress);
        $request->billTo = ($builtBillingAddress !== null) ? $builtBillingAddress : $this->buildAddressForTax($shippingAddress);
        $request->shipTo = $this->buildAddressForTax($shippingAddress);

        $taxService = new \stdClass();

        $shippingCountry = $shippingAddress->getCountryId();
        if ($shippingCountry == 'CA' || $shippingCountry == 'US') {
            $request->shipFrom = $this->buildStoreShippingFromForTax();
            $taxService = $this->buildTaxOrderConfigurationForTax($taxService);
        }

        $taxService->run = 'true';

        $nexusRegions = $this->taxConfig->getTaxNexusRegions(" ");
        if (!empty($nexusRegions)) {
            $taxService->nexus = $nexusRegions;
        }

        if ($shippingCountry != 'US') {
            $taxService->sellerRegistration = $this->taxConfig->getTaxMerchantVat();
            if ($shippingAddress->getVatId() != null) {
                $taxService->buyerRegistration = $shippingAddress->getVatId();
            }
        }

        $request->taxService = $taxService;

        if (! $items = $this->buildItemNodeFromShippingItems($quoteTaxDetails, $storeId)) {
            return null;
        }

        $request->item = $items;

        $response = $this->placeRequest($request);

        if (!$this->isValidResponse($response)) {
            $this->logger->error('Not valid response: ' . $this->serializer->serialize($response));
            return null;
        }

        // convert stdObjects to arrays
        return $this->serializer->unserialize($this->serializer->serialize($response));
    }

    private function placeRequest($request)
    {
        try {
            $isValidShipToAddress = $this->validateAddress($request->shipTo);
            if ($isValidShipToAddress) {
                $this->logger->debug([__METHOD__ => (array) $request]);
                $response = $this->client->runTransaction($request);
                $this->logger->debug([__METHOD__ => (array) $response]);
                return $response;
            } else {
                $this->logger->error("Tax: unable to request. Missing shipTo information");
            }
        } catch (\Exception $e) {
            $this->logger->error("Tax: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Validate response
     *
     * @return bool
     */
    public function isValidResponse($response)
    {

        if ($response != null) {
            if ($response->reasonCode == self::SUCCESS_REASON_CODE && property_exists($response, 'taxReply')) {
                return true;
            }
        }
        $this->logger->error("Tax: Invalid CyberSource Tax response.");

        return false;
    }

    /**
     * Build order items
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
     * @return array
     */
    private function buildItemNodeFromShippingItems(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails,
        $storeId = null
    ) {
        $lineItems = [];
        $items = $quoteTaxDetails->getItems();
        $itemId = 0;

        if (empty($items)) {
            return $lineItems;
        }

        $parentQuantities = [];

        foreach ($items as $i => $item) {
            /** @var \Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface $extensionAttributes */
            if (!$extensionAttributes = $item->getExtensionAttributes()) {
                continue;
            }

            $lineItem = new \stdClass();
            $id = $i;
            $parentId = $item->getParentCode();
            $unitPrice = (float) $extensionAttributes->getPriceForTaxCalculation() ?: $item->getUnitPrice();
            $quantity = (int)$item->getQuantity();
            $discount = (float)$item->getDiscountAmount() / $quantity;

            if ($extensionAttributes->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $parentQuantities[$id] = $quantity;
                if ($extensionAttributes->getPriceType() == \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
                    continue;
                }
            }

            if (isset($parentQuantities[$parentId])) {
                $quantity *= $parentQuantities[$parentId];
            }

            if (!$this->taxData->applyTaxAfterDiscount($storeId)) {
                $discount = 0;
            }

            if (!$taxClassCodeId = $item->getTaxClassKey()->getValue()) {
                $taxClassCodeId = $this->taxData->getDefaultProductTaxClass();
            }

            if ($this->productMetadata->getEdition() == 'Enterprise' &&
                $extensionAttributes->getProductType() ==
                \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD
            ) {
                $giftTaxClassId = $this->config->getValue('tax/classes/wrapping_tax_class');
                if ($giftTaxClassId) {
                    $taxClassCodeId = $giftTaxClassId;
                }
            }

            $taxClass = $this->taxClassRepository->get($taxClassCodeId);
            $taxCode = $taxClass->getClassName();

            $lineItem->id = $id;
            $lineItem->unitPrice = $this->requestDataHelper->formatAmount($unitPrice - $discount);

            if ($lineItem->unitPrice <= 0) {
                continue;
            }

            $lineItem->quantity = (string)$quantity;
            $lineItem->productCode = $taxCode;
            $lineItem->productName = $extensionAttributes->getProductName();
            $lineItem->productSKU = $extensionAttributes->getSku();

            $lineItems[] = $lineItem;

            $itemId++;
        }

        return $lineItems;
    }

    /**
     * @param \Magento\Customer\Model\Data\Address $address
     * @return \stdClass $builtAddress
     */
    private function buildAddressForTax($address)
    {
        $builtAddress = new \stdClass();

        if ($address->getCountryId()) {
            $region = $address->getRegion();
            if ($address->getCountryId() == 'CA' || $address->getCountryId() == 'US') {
                $builtAddress->state = $region->getRegionCode();
            } else {
                $builtAddress->state = $region->getRegion();
            }
        }

        if ($address->getPostcode()) {
            $builtAddress->postalCode = $address->getPostcode();
        }

        if ($address->getFirstname()) {
            $builtAddress->firstName = $address->getFirstname();
        }

        if ($address->getLastname()) {
            $builtAddress->lastName = $address->getLastname();
        }

        if ($street = $address->getStreet()) {
            foreach ($street as $i => $v) {
                if (empty($v)) {
                    continue;
                }
                $builtAddress->{'street' . ($i + 1)} = $v;
            }
        }

        if ($address->getCity()) {
            $builtAddress->city = $address->getCity();
        }

        if ($address->getCountryId()) {
            $builtAddress->country = $address->getCountryId();
        }

        if ($this->validateAddress($builtAddress)) {
            return $builtAddress;
        }

        return null;
    }

    /**
     * Retrieve Tax Shipping From configuration
     *
     * @return \stdClass
     */
    private function buildStoreShippingFromForTax()
    {
        $shipFrom = new \stdClass();
        $shipFrom->city = $this->taxConfig->getTaxShipFromCity();
        $shipFrom->country = $this->taxConfig->getTaxShipFromCountry();
        $shipFrom->state = $this->taxConfig->getTaxShipFromRegion();
        $shipFrom->postalCode = $this->taxConfig->getTaxShipFromPostcode();

        return $shipFrom;
    }

    /**
     * Build TaxService order node
     *
     * @param \stdClass $taxService
     * @return \stdClass
     */
    private function buildTaxOrderConfigurationForTax(\stdClass $taxService)
    {
        // orderAcceptance
        $taxService->orderAcceptanceCity = $this->taxConfig->getTaxAcceptanceCity();
        $taxService->orderAcceptanceCountry = $this->taxConfig->getTaxAcceptanceCountry();
        $taxService->orderAcceptanceState = $this->taxConfig->getTaxAcceptanceRegion();
        $taxService->orderAcceptancePostalCode = $this->taxConfig->getTaxAcceptancePostcode();

        // orderOrigin
        $taxService->orderOriginCity = $this->taxConfig->getTaxOriginCity();
        $taxService->orderOriginCountry = $this->taxConfig->getTaxOriginCountry();
        $taxService->orderOriginState = $this->taxConfig->getTaxOriginRegion();
        $taxService->orderOriginPostalCode = $this->taxConfig->getTaxOriginPostcode();

        return $taxService;
    }

    /**
     * @param $address
     * @return bool
     */
    public function validateAddress($address)
    {
        if ($address === null) {
            return false;
        }
        $validationKeys = ['city', 'state', 'postalCode', 'country'];

        foreach ($validationKeys as $key) {
            if ((empty($address->{$key}) || $address->{$key} == null)) {
                return false;
            }
        }

        return true;
    }
}
