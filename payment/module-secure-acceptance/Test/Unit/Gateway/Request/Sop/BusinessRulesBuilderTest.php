<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class BusinessRulesBuilderTest extends TestCase
{
    /** @var BusinessRulesBuilder */
    private $businessRulesBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->businessRulesBuilder = new BusinessRulesBuilder(
            $this->subjectReaderMock,
            $this->configMock
        );
    }

    /**
     * @dataProvider dataProviderTestBuild
     *
     * @param $config
     * @param $expected
     */
    public function testBuild($config, $expected)
    {

        $this->configMock->method('getIgnoreAvs')->willReturn($config['ignore_avs']);
        $this->configMock->method('getIgnoreCvn')->willReturn($config['ignore_cvn']);

        $this->assertEquals($this->businessRulesBuilder->build([]), $expected);
    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'config' => [
                    'ignore_avs' => 1,
                    'ignore_cvn' => 1,
                ],
                'expected' => [
                    'ignore_avs' => 'true',
                    'ignore_cvn' => 'true',
                ],
            ],
            [
                'config' => [
                    'ignore_avs' => 0,
                    'ignore_cvn' => 0,
                ],
                'expected' => [
                ],
            ],
            [
                'config' => [
                    'ignore_avs' => 1,
                    'ignore_cvn' => 0,
                ],
                'expected' => [
                    'ignore_avs' => 'true',
                ],
            ],
            [
                'config' => [
                    'ignore_avs' => 0,
                    'ignore_cvn' => 1,
                ],
                'expected' => [
                    'ignore_cvn' => 'true',
                ],
            ],
        ];
    }
}
