<?php declare(strict_types=1);
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Request\Rest;

use PHPUnit\Framework\TestCase;

class OrderItemsBuilderTest extends TestCase
{
    /** @var \CyberSource\Core\Gateway\Request\Rest\OrderItemsBuilder */
    private $itemsDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;
    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface | \PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentDOMock;
    /**
     * @var Magento\Payment\Model\InfoInterface | \PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMock;
    private $collectionMock;
    /**
     * @var Magento\Sales\Model\AbstractModel | \PHPUnit\Framework\MockObject\MockObject
     */
    private $itemMock;
    /**
     * @var Magento\Sales\Model\Order\Item | \PHPUnit\Framework\MockObject\MockObject
     */
    private $orderItemMock;
    /**
     * @var array
     */
    private $buildSubject;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(\Magento\Payment\Model\InfoInterface::class, [
            'getOrder',
            'getCreditmemo',
            'getInvoice',
            'getCreatedInvoice',
            'encrypt',
            'decrypt',
            'setAdditionalInformation',
            'hasAdditionalInformation',
            'unsAdditionalInformation',
            'getAdditionalInformation',
            'getMethodInstance',
            'getData',
        ]);
        $this->collectionMock = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Collection\AbstractCollection::class,
            [
                'getAllItems'
            ]
        );
        $this->itemMock = $this->createPartialMock(\Magento\Sales\Model\AbstractModel::class, [
            'getProductType',
            'getOrderItem',
            'getQty',
            'getQtyOrdered',
            'getBasePrice',
            'getBaseDiscountAmount',
            'getName',
            'getSku',
            'getBaseTaxAmount',
        ]);

        $this->orderItemMock = $this->createMock(\Magento\Sales\Model\Order\Item::class);

        $this->subjectReaderMock->method('readPayment')->with(['payment' => $this->paymentDOMock])->willReturn($this->paymentDOMock);

        $this->paymentDOMock->method('getPayment')->willReturn($this->paymentMock);

        $this->buildSubject = ['payment' => $this->paymentDOMock];
    }

    public function testBuildCreditMemoItems()
    {
        $items = [
            0 => $this->itemMock
        ];
        $this->itemsDataBuilder = new \CyberSource\Core\Gateway\Request\Rest\OrderItemsBuilder(
            $this->subjectReaderMock,
            'creditmemo'
        );
        $this->paymentMock->method('getCreditmemo')->willReturn($this->collectionMock);
        $this->collectionMock->method('getAllItems')->willReturn($items);
        $this->itemMock->method('getProductType')->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_BUNDLE);
        $this->itemMock->method('getBasePrice')->willReturn(100);
        $this->assertEquals(
            [
                'orderInformation' =>
                    [
                        'lineItems' => []
                    ]
            ],
            $this->itemsDataBuilder->build($this->buildSubject)
        );
    }

    public function testBuildInvoiceItems()
    {
        $type = 'simple';
        $name = 'productName';
        $sku = 'productSku';
        $price = 100;
        $discount = 10;
        $qty = 2.0;
        $tax = 30;

        $items = [
            0 => $this->itemMock
        ];

        $result = [
            0 => [
                'productName' => $name,
                'productSKU' => $sku,
                'productCode' => $type,
                'quantity' => (int)$qty,
                'unitPrice' => sprintf('%.2F', $price - $discount / $qty),
                'taxAmount' => sprintf('%.2F', $tax)
            ]
        ];

        //sprintf('%.2F', $price)

        $this->itemsDataBuilder = new \CyberSource\Core\Gateway\Request\Rest\OrderItemsBuilder(
            $this->subjectReaderMock,
            'invoice'
        );
        $this->paymentMock->method('getInvoice')->willReturn(null);
        $this->paymentMock->method('getCreatedInvoice')->willReturn($this->collectionMock);
        $this->collectionMock->method('getAllItems')->willReturn($items);
        $this->itemMock->method('getProductType')->willReturn($type);
        $this->itemMock->method('getBasePrice')->willReturn($price);
        $this->itemMock->method('getBaseDiscountAmount')->willReturn($discount);
        $this->itemMock->method('getQty')->willReturn($qty);
        $this->itemMock->method('getName')->willReturn($name);
        $this->itemMock->method('getSku')->willReturn($sku);
        $this->itemMock->method('getBaseTaxAmount')->willReturn($tax);

        $this->assertEquals(
            [
                'orderInformation' =>
                    [
                        'lineItems' => $result
                    ]
            ],
            $this->itemsDataBuilder->build($this->buildSubject)
        );
    }

    public function testBuildOrderItems()
    {
        $type = 'simple';
        $name = 'productName';
        $sku = 'productSku';
        $price = 100;
        $discount = 10;
        $qty = 2.0;
        $tax = 30;

        $items = [
            0 => $this->itemMock
        ];

        $result = [
            0 => [
                'productName' => $name,
                'productSKU' => $sku,
                'productCode' => $type,
                'quantity' => (int)$qty,
                'unitPrice' => sprintf('%.2F', $price - $discount / $qty),
                'taxAmount' => sprintf('%.2F', $tax),
            ]
        ];

        $this->itemsDataBuilder = new \CyberSource\Core\Gateway\Request\Rest\OrderItemsBuilder(
            $this->subjectReaderMock,
            'order'
        );
        $this->paymentMock->method('getOrder')->willReturn($this->collectionMock);
        $this->collectionMock->method('getAllItems')->willReturn($items);

        $this->itemMock->method('getProductType')->willReturn(null);
        $this->itemMock->method('getOrderItem')->willReturn($this->orderItemMock);
        $this->itemMock->method('getBasePrice')->willReturn($price);
        $this->itemMock->method('getQty')->willReturn(null);
        $this->itemMock->method('getQtyOrdered')->willReturn($qty);
        $this->itemMock->method('getBaseDiscountAmount')->willReturn($discount);
        $this->itemMock->method('getName')->willReturn($name);
        $this->itemMock->method('getSku')->willReturn($sku);
        $this->itemMock->method('getBaseTaxAmount')->willReturn($tax);

        $this->orderItemMock->method('getProductType')->willReturn($type);

        $this->assertEquals( [
            'orderInformation' =>
                [
                    'lineItems' => $result
                ]
        ], $this->itemsDataBuilder->build($this->buildSubject));
    }

    public function testBuildOrderItemsWithoutPrice()
    {
        $items = [
            0 => $this->itemMock
        ];
        $this->itemsDataBuilder = new \CyberSource\Core\Gateway\Request\Rest\OrderItemsBuilder(
            $this->subjectReaderMock,
            'order'
        );
        $this->paymentMock->method('getOrder')->willReturn($this->collectionMock);
        $this->collectionMock->method('getAllItems')->willReturn($items);
        $this->itemMock->method('getProductType')->willReturn('simple');
        $this->itemMock->method('getBasePrice')->willReturn(0);
        $this->assertEquals([
            'orderInformation' =>
                [
                    'lineItems' => []
                ]
        ], $this->itemsDataBuilder->build($this->buildSubject));
    }
}
