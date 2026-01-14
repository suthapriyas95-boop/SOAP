<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Observer;

use PHPUnit\Framework\TestCase;

class AddShortcutsObserverTest extends TestCase
{

    /**
     * @var \CyberSource\GooglePay\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var AddShortcutsObserver
     */
    protected $observer;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\CyberSource\GooglePay\Gateway\Config\Config::class);

        $this->observer = new AddShortcutsObserver($this->configMock);
    }

    public function testExecute()
    {
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);

        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getContainer']);
        $observer->method('getEvent')->willReturn($event);

        $this->configMock->method('isActive')->willReturn(true);

        $shortcutContainerMock = $this->createMock(\Magento\Catalog\Block\ShortcutButtons::class);
        $event->method('getContainer')->willReturn($shortcutContainerMock);

        $layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $shortcutContainerMock->method('getLayout')->willReturn($layoutMock);

        $blockMock = $this->createPartialMock(
            \Magento\Framework\View\Element\Template::class,
            [
                'setIsInCatalogProduct',
                'setShowOrPosition',
                'toHtml'
            ]
        );

        $blockMock->method('setShowOrPosition')->willReturnSelf();
        $blockMock->method('setIsInCatalogProduct')->willReturnSelf();
        $layoutMock->method('createBlock')->with(\CyberSource\GooglePay\Block\Button::class)->willReturn($blockMock);

        $shortcutContainerMock->expects(static::once())->method('addShortcut')->with($blockMock);

        $this->observer->execute($observer);
    }

    public function testExecuteDisabled()
    {
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);

        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getContainer']);
        $observer->method('getEvent')->willReturn($event);

        $this->configMock->method('isActive')->willReturn(false);

        $shortcutContainerMock = $this->createMock(\Magento\Catalog\Block\ShortcutButtons::class);
        $event->method('getContainer')->willReturn($shortcutContainerMock);

        $layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $shortcutContainerMock->method('getLayout')->willReturn($layoutMock);

        $blockMock = $this->createPartialMock(
            \Magento\Framework\View\Element\Template::class,
            [
                'setIsInCatalogProduct',
                'setShowOrPosition',
                'toHtml'
            ]
        );

        $blockMock->method('setShowOrPosition')->willReturnSelf();
        $blockMock->method('setIsInCatalogProduct')->willReturnSelf();
        $layoutMock->method('createBlock')->with(\CyberSource\GooglePay\Block\Button::class)->willReturn($blockMock);

        $shortcutContainerMock->expects(static::never())->method('addShortcut')->with($blockMock);

        $this->observer->execute($observer);
    }
}
