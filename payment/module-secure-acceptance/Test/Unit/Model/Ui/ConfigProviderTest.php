<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace CyberSource\SecureAcceptance\Test\Unit\Model\Ui;

use CyberSource\SecureAcceptance\Gateway\Http\Client\ClientMock;
use CyberSource\SecureAcceptance\Model\Ui\ConfigProvider;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfig()
    {
        $this->markTestSkipped('Needs rework');

        $configProvider = new ConfigProvider();

        static::assertEquals(
            [
                'payment' => [
                    ConfigProvider::CODE => [
                        'transactionResults' => [
                            ClientMock::SUCCESS => __('Success'),
                            ClientMock::FAILURE => __('Fraud')
                        ]
                    ]
                ]
            ],
            $configProvider->getConfig()
        );
    }
}
