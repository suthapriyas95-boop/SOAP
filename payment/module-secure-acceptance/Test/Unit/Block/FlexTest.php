<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Block;

use PHPUnit\Framework\TestCase;

class FlexTest extends TestCase
{
    /** @var Flex */
    private $flex;

    /** @var \Magento\Framework\View\Element\Template\Context | \PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var array */
    private $dataMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->dataMock = [];
        $this->flex = new Flex(
            $this->contextMock,
            $this->configMock,
            $this->dataMock
        );
    }

    /**
     * @param $expected
     * @param $configValue
     * @dataProvider dataProviderTestIsSandbox
     */
    public function testIsSandbox($expected, $configValue)
    {
        $this->configMock->expects(static::any())->method('isTestMode')->willReturn($configValue);

        static::assertEquals($expected, $this->flex->isSandbox());
    }

    public function dataProviderTestIsSandbox()
    {
        return [
            ['expected' => true, 'configValue' => true],
        ];

    }

}
