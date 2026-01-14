<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Block;

use PHPUnit\Framework\TestCase;

class ButtonTest extends TestCase
{

    /**
     * @var \Magento\Framework\View\Element\Template\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Payment\Model\MethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $methodInterfaceMock;

    /**
     * @var \Magento\Checkout\Model\ConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProviderInterfaceMock;

    /**
     * @var \Magento\Framework\Math\Random|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $randomMock;

    /**
     * @var \Magento\Directory\Model\AllowedCountries|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $allowedCountriesMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var Button
     */
    protected $button;

    /**
     * @var string
     */
    protected $alias;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializerMock;
    /**
     * @var \Magento\Framework\Escaper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $escaperMock;

    protected function setUp()
    {

        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->sessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->methodInterfaceMock = $this->createMock(\Magento\Payment\Model\MethodInterface::class);
        $this->configProviderInterfaceMock = $this->createMock(\Magento\Checkout\Model\ConfigProviderInterface::class);
        $this->randomMock = $this->createMock(\Magento\Framework\Math\Random::class);
        $this->allowedCountriesMock = $this->createMock(\Magento\Directory\Model\AllowedCountries::class);
        $this->jsonSerializerMock = $this->createMock(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);

        $this->escaperMock = $this->createMock(\Magento\Framework\Escaper::class);
        $this->escaperMock->method('escapeHtml')->willReturnArgument(0);
        $this->contextMock->method('getEscaper')->willReturn($this->escaperMock);

        $this->alias = 'some alias';

        $this->button = new \CyberSource\GooglePay\Block\Button(
            $this->contextMock,
            $this->sessionMock,
            $this->methodInterfaceMock,
            $this->configProviderInterfaceMock,
            $this->randomMock,
            $this->allowedCountriesMock,
            $this->jsonSerializerMock,
            $this->registryMock,
            'sometemplate.phtml',
            [
                'alias' => $this->alias,
                'show_or_position' => 'after',
                'shortcut_html_id' => 'htmlid',
            ]
        );

    }

    public function testSetIsInCatalogProduct()
    {
        $this->button->setIsInCatalogProduct(true);
        static::assertEquals(true, $this->button->getIsInCatalogProduct());
        $this->button->setIsInCatalogProduct(false);
        static::assertEquals(false, $this->button->getIsInCatalogProduct());
    }

    public function testGetAlias()
    {
        static::assertEquals($this->alias, $this->button->getAlias());
    }

    public function testIsOrPositionAfter()
    {
        self::assertEquals(true, $this->button->isOrPositionAfter());
    }

    public function testIsOrPositionBefore()
    {
        self::assertEquals(false, $this->button->isOrPositionBefore());
    }

    /**
     * @param $isVirtual
     * @param $isInCatalogProduct
     * @param $expected
     * @dataProvider dataProviderGetJsonConfig
     */
    public function testGetJsonConfig($isVirtual, $isInCatalogProduct, $expected)
    {

        $providerConfig = [
            'someProvider' => 'value',
        ];

        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $productMock->method('isVirtual')->willReturn($isVirtual);
        $productMock->method('getId')->willReturn(12);

        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteMock->method('isVirtual')->willReturn($isVirtual);
        $this->sessionMock->method('getQuote')->willReturn($quoteMock);

        $this->registryMock->method('registry')->with('current_product')->willReturn($productMock);

        $this->configProviderInterfaceMock->method('getConfig')->willReturn([
            'payment' => [
                'cybersource_googlepay' =>
                    $providerConfig

            ],
        ]);

        $this->button->setIsInCatalogProduct($isInCatalogProduct);

        $this->jsonSerializerMock->method('serialize')->willReturnArgument(0);

        static::assertEquals(
            array_merge($expected, $providerConfig),
            $this->button->getJsonConfig()
        );
    }

    public function dataProviderGetJsonConfig()
    {
        return [
            [
                'isVirtual' => false,
                'isInCatalogProduct' => false,
                'expected' => [
                    'shortcutContainerClass' => '#htmlid',
                    'isCatalogProduct' => false,
                    'requiresShipping' => true,
                ]
            ],
            [
                'isVirtual' => false,
                'isInCatalogProduct' => true,
                'expected' => [
                    'shortcutContainerClass' => '#htmlid',
                    'isCatalogProduct' => true,
                    'requiresShipping' => true,
                ]
            ],
            [
                'isVirtual' => true,
                'isInCatalogProduct' => false,
                'expected' => [
                    'shortcutContainerClass' => '#htmlid',
                    'isCatalogProduct' => false,
                    'requiresShipping' => false,
                ]
            ],
            [
                'isVirtual' => true,
                'isInCatalogProduct' => true,
                'expected' => [
                    'shortcutContainerClass' => '#htmlid',
                    'isCatalogProduct' => true,
                    'requiresShipping' => false,
                ]
            ],
        ];
    }

}
