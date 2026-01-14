<?php declare(strict_types = 1);

namespace CyberSource\ThreeDSecure\Webapi\Error;

use PHPUnit\Framework\TestCase;

class ProcessorPluginTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Exception
     */
    private $exceptionMock;
    /**
     * @var \Magento\Framework\Webapi\ErrorProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $errorProcessorMock;

    /** @var ProcessorPlugin */
    private $processorPlugin;

    protected function setUp()
    {
        $this->errorProcessorMock = $this->createMock(\Magento\Framework\Webapi\ErrorProcessor::class);
        $this->exceptionMock = $this->createMock(\Exception::class);

//        $this
        $this->processorPlugin = new ProcessorPlugin();
    }

    public function testBeforeMaskExceptionEnrolled()
    {

        $paEnrolledExceptionMock = $this->createMock(\CyberSource\ThreeDSecure\Gateway\PaEnrolledException::class);
        $exception = new \Exception('test exception', 0, $paEnrolledExceptionMock);

        $this->assertEquals(
            [$paEnrolledExceptionMock],
            $this->processorPlugin->beforeMaskException($this->errorProcessorMock, $exception)
        );
    }

    public function testBeforeMaskExceptionNonEnrolled()
    {

        $this->assertEquals(
            [$this->exceptionMock],
            $this->processorPlugin->beforeMaskException($this->errorProcessorMock, $this->exceptionMock)
        );
    }
}
