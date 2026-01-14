<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\KlarnaFinancial\Model;


class Cron
{

    const COMMAND_CODE_CHECK_STATUS = 'check_status';

    /**
     * @var \CyberSource\KlarnaFinancial\Gateway\Config\Config
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

    public function __construct(
        \CyberSource\KlarnaFinancial\Gateway\Config\Config $config,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Payment\Gateway\Command\CommandManagerPoolInterface $commandManagerPool,
        \CyberSource\Core\Model\LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->commandManagerPool = $commandManagerPool;
        $this->logger = $logger;
    }

    public function execute()
    {
        if (!$this->config->isActive()) {
            return;
        }

        foreach ($this->getPendingPayments() as $payment) {
            try {
                $this->commandManagerPool
                    ->get(\CyberSource\KlarnaFinancial\Model\Ui\ConfigProvider::CODE)
                    ->executeByCode(static::COMMAND_CODE_CHECK_STATUS, $payment);
            } catch (\Exception $e) {
                $this->logger->error('An occurred error while running Klarna cron: ' . $e->getMessage());
            }
        }
    }

    private function getPendingPayments()
    {

        $paymentCollection = $this->paymentCollectionFactory->create();
        $paymentCollection
            ->addFieldToFilter(
                'main_table.method',
                \CyberSource\KlarnaFinancial\Model\Ui\ConfigProvider::CODE
            )
            ->addFieldToFilter(
                'order_table.state',
                [
                    'in' => ['payment_review']
                ]
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
