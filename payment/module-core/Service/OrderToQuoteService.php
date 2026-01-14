<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\Service;


class OrderToQuoteService implements OrderToQuoteInterface
{

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\DataObjectFactory $dataObjectFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->cartRepository = $cartRepository;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    public function convertOrderToQuote($orderId, $quote = null)
    {

        if ($quote == null) {
            $quote = $this->quoteFactory->create();
        }

        $order = $this->orderRepository->get($orderId);

        foreach ($order->getItemsCollection() as $orderItem) {

            if ($orderItem->getParentItem() !== null) {
                continue;
            }

            $product = $this->productRepository->getById($orderItem->getProductId(), false, $order->getStoreId(), true);

            if (!$product->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Product not found.'));
            }

            $info = $orderItem->getProductOptionByCode('info_buyRequest');
            $info = $this->dataObjectFactory->create(['data' => $info]);

            $info->setQty($orderItem->getQtyOrdered());

            $quote->addProduct($product, $info);
        }

        //trigger addresses population to make sure that collectTotals will do the job
        $quote->getBillingAddress();
        $quote->getShippingAddress()->setCollectShippingRates(true);

        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();

        $this->cartRepository->save($quote);

        return $quote;
    }
}
