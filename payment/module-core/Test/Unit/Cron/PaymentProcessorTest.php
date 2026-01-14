<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Cron;

use CyberSource\Core\Cron\PaymentProcessor;
use PHPUnit\Framework\TestCase;

class PaymentProcessorTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Model\AbstractGatewayConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandManagerMock;

    /**
     * @var \Magento\Payment\Gateway\CommandInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandMock;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var string
     */
    protected $paymentCode;

    /**
     * @var string[]
     */
    protected $paymentStates;

    /**
     * @var PaymentProcessor
     */
    protected $paymentProcessor;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionMock;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $selectMock;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerPoolInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandManagerPoolMock;

    /**
     * @var string
     */
    protected $commandCode;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\CyberSource\Core\Model\AbstractGatewayConfig::class);
        $this->collectionFactoryMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory::class);
        $this->collectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class);
        $this->commandManagerMock = $this->createMock(\Magento\Payment\Gateway\Command\CommandManager::class);
        $this->commandManagerPoolMock = $this->createMock(\Magento\Payment\Gateway\Command\CommandManagerPoolInterface::class);

        $this->commandMock = $this->createMock(\Magento\Payment\Gateway\CommandInterface::class);
        $this->loggerMock = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);
        $this->selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->paymentCode = 'some_code';
        $this->paymentStates = ['some_state'];
        $this->commandCode = 'status';

        $this->collectionMock->method('getTable')->willReturnArgument(0);

        $this->paymentProcessor = new PaymentProcessor(
            $this->configMock,
            $this->collectionFactoryMock,
            $this->commandManagerPoolMock,
            $this->loggerMock,
            $this->commandCode,
            $this->paymentCode,
            $this->paymentStates
        );
    }

    public function testExecute()
    {
        $this->configMock->method('isActive')->willReturn(true);

        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->collectionMock->method('addFieldToFilter')->withConsecutive(
            [
                'main_table.method',
                $this->paymentCode
            ],
            [
                'order_table.state',
                ['in' => $this->paymentStates],
            ]
        )->willReturnSelf();

        $this->collectionMock->method('getSelect')->willReturn($this->selectMock);

        $this->selectMock->method('joinLeft')->with(
            ['order_table' => 'sales_order'],
            'main_table.parent_id = order_table.entity_id',
            ['status', 'quote_id']
        )->willReturnSelf();

        $this->selectMock->method('order')->with('entity_id DESC');

        $items = [
            $this->createMock(\Magento\Sales\Model\Order\Payment::class),
            $this->createMock(\Magento\Sales\Model\Order\Payment::class),
        ];

        $this->collectionMock->method('getIterator')->willReturn(new \ArrayIterator($items));

        $this->commandManagerPoolMock->method('get')->with($this->paymentCode)->willReturn($this->commandManagerMock);

        $this->commandManagerMock
            ->expects(static::exactly(count($items)))
            ->method('executeByCode')
            ->withConsecutive(
                ...array_map(function ($item) {
                    return [$this->commandCode, $item];
                }, $items)
            );

        $this->paymentProcessor->execute();
    }
}
