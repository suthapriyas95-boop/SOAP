<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Cron;

class PaymentProcessor
{
    /**
     * @var \CyberSource\Core\Model\AbstractGatewayConfig
     */
    private $config;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
     */
    private $paymentCollectionFactory;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerPoolInterface
     */
    private $commandManagerPool;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $commandCode;

    /**
     * @var string
     */
    private $paymentCode;

    /**
     * @var array
     */
    private $paymentStates;


    /**
     * @param \CyberSource\Core\Model\AbstractGatewayConfig $config
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory
     * @param \Magento\Payment\Gateway\Command\CommandManagerPoolInterface $commandManagerPool
     * @param \CyberSource\Core\Model\LoggerInterface $logger
     * @param string $commandCode
     * @param string $paymentCode
     * @param array $paymentStates
     */
    public function __construct(
        \CyberSource\Core\Model\AbstractGatewayConfig $config,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Payment\Gateway\Command\CommandManagerPoolInterface $commandManagerPool,
        \CyberSource\Core\Model\LoggerInterface $logger,
        string $commandCode,
        string $paymentCode,
        array $paymentStates
    ) {
        $this->config = $config;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->commandManagerPool = $commandManagerPool;
        $this->logger = $logger;
        $this->commandCode = $commandCode;
        $this->paymentCode = $paymentCode;
        $this->paymentStates = $paymentStates;
    }

    public function execute()
    {
        if (! $this->config->isActive()) {
            return;
        }

        foreach ($this->getPaymentCollection() as $payment) {
            try {
                $this->commandManagerPool
                    ->get($this->paymentCode)
                    ->executeByCode($this->commandCode, $payment);
            } catch (\Exception $e) {
                $this->logger->error('An error occurred while processing payments: ' . $e->getMessage());
            }
        }
    }

    /**
     * @return \Magento\Sales\Model\ResourceModel\Order\Payment\Collection
     */
    private function getPaymentCollection()
    {
        $paymentCollection = $this->paymentCollectionFactory->create();
        $paymentCollection
            ->addFieldToFilter(
                'main_table.method',
                $this->paymentCode
            )
            ->addFieldToFilter(
                'order_table.state',
                ['in' => $this->paymentStates]
            );

        $paymentCollection->getSelect()
            ->joinleft(
                ['order_table' => $paymentCollection->getTable('sales_order')],
                'main_table.parent_id = order_table.entity_id',
                ['status', 'quote_id']
            )->order('entity_id DESC');

        return $paymentCollection;
    }
}
