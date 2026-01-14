<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class SignatureDecoratorTest extends TestCase
{
    /** @var SignatureDecorator */
    private $signatureDecorator;

    /** @var \Magento\Framework\ObjectManager\TMapFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $tmapFactory;

    /** @var \CyberSource\SecureAcceptance\Model\SignatureManagementInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $signatureManagement;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime | \PHPUnit_Framework_MockObject_MockObject */
    private $dateTime;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var array */
    private $builders;

    protected function setUp()
    {
        $this->tmapFactory = $this->createMock(\Magento\Framework\ObjectManager\TMapFactory::class);
        $this->signatureManagement = $this->createMock(\CyberSource\SecureAcceptance\Model\SignatureManagementInterface::class);
        $this->dateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->config = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->builders = [];
        $this->signatureDecorator = new SignatureDecorator(
            $this->tmapFactory,
            $this->signatureManagement,
            $this->dateTime,
            $this->config,
            $this->subjectReader,
            $this->builders
        );
    }

    public function testMissing()
    {
        $this->fail('Test not yet implemented');
    }
}
