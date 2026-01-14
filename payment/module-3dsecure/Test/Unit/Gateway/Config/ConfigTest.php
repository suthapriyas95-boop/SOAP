<?php declare(strict_types = 1);

namespace CyberSource\ThreeDSecure\Gateway\Config;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @var Config */
    private $config;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $scopeConfig;

    protected function setUp()
    {
        $this->scopeConfig = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->config = new Config(
            $this->scopeConfig
        );
    }

    /**
     * @dataProvider dataProviderTestMethods
     */
    public function testIsTestMode($path, $value, $method)
    {
        $this->scopeConfig
            ->expects(static::once())
            ->method('getValue')
            ->with('payment/chcybersource/' . $path)
            ->willReturn($value);

        $this->assertEquals($value, $this->config->$method());
    }

    public function dataProviderTestMethods()
    {
        return [
            ['path' => 'test_mode_3ds', 'value' => true, 'method'=> 'isTestMode'],
            ['path' => 'test_mode_3ds', 'value' => false, 'method'=> 'isTestMode'],
            ['path' => 'org_unit_id_3ds', 'value' => true, 'method'=> 'getOrgUnitId'],
            ['path' => 'org_unit_id_3ds', 'value' => false, 'method'=> 'getOrgUnitId'],
            ['path' => 'api_id_3ds', 'value' => true, 'method'=> 'getApiId'],
            ['path' => 'api_id_3ds', 'value' => false, 'method'=> 'getApiId'],
            ['path' => 'api_key_3ds', 'value' => true, 'method'=> 'getApiKey'],
            ['path' => 'api_key_3ds', 'value' => false, 'method'=> 'getApiKey'],
        ];
    }
}
