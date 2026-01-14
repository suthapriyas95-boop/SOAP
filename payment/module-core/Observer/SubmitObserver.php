<?php

namespace CyberSource\Core\Observer;

class SubmitObserver implements \Magento\Framework\Event\ObserverInterface
{

    const MINIMAL_VERSION = '2.2.5';

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * SubmitObserver constructor.
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     */
    public function __construct(
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->productMetadata = $productMetadata;
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (version_compare($this->productMetadata->getVersion(), self::MINIMAL_VERSION, '>=')) {
            return;
        }

        /** @var \Magento\Quote\Observer\Webapi\SubmitObserver $submitObserver */
        $submitObserver = $this->objectManager->get('Magento\Quote\Observer\Webapi\SubmitObserver');

        $submitObserver->execute($observer);
    }
}
