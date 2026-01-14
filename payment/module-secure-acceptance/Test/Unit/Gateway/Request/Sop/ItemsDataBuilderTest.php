<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class ItemsDataBuilderTest extends TestCase
{
    /** @var ItemsDataBuilder */
    private $itemsDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var \Magento\Tax\Model\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $taxConfig;

    /** @var string */
    private $objectName;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->taxConfig = $this->createMock(\Magento\Tax\Model\Config::class);
        $this->objectName = null;
        $this->itemsDataBuilder = new ItemsDataBuilder(
            $this->subjectReader,
            $this->taxConfig,
            $this->objectName
        );
    }

    public function testMissing()
    {
        $this->fail('Test not yet implemented');
    }
}
