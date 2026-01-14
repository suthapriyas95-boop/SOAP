<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class MddBuilderTest extends TestCase
{
    /** @var MddBuilder */
    private $mddBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var \Magento\Quote\Api\CartRepositoryInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $cartRepository;

    /** @var \CyberSource\SecureAcceptance\Gateway\Request\Soap\DecisionManagerMddBuilder | \PHPUnit_Framework_MockObject_MockObject */
    private $mddBuilderSoap;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->cartRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->mddBuilderSoap = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Request\Soap\DecisionManagerMddBuilder::class);
        $this->mddBuilder = new MddBuilder(
            $this->subjectReader,
            $this->cartRepository,
            $this->mddBuilderSoap
        );
    }

    public function testMissing()
    {
        $this->fail('Test not yet implemented');
    }
}
