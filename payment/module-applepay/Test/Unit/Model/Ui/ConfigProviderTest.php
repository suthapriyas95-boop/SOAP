<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\ApplePay\Test\Unit\Model\Ui;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CancelTest
 * @package CyberSource\BankTransfer\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     *
     * @var CyberSource\ApplePay\Block\Form 
     */
    private $unit;
    
    protected function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder(\CyberSource\ApplePay\Gateway\Config\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \CyberSource\ApplePay\Model\Ui\ConfigProvider::class,
            ['config' => $this->configMock]
        );
    }
    
    public function testGetConfig()
    {
        $this->assertEquals(
            [
                'payment' => [
                    'cybersource_applepay' => [
                        'active' => null,
                        'title' => null,
                        'code' => 'cybersource_applepay',
                        'vaultCode' => 'cybersource_applepay_token'
                    ],
                ],
                'vault' => [
                    'cybersource_applepay_token' => [
                        'is_enabled' => false
                    ]
                ]
        ], 
            $this->unit->getConfig()
        );
    }
}