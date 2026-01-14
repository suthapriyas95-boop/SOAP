<?php declare(strict_types = 1);

namespace CyberSource\ThreeDSecure\Gateway;

use PHPUnit\Framework\TestCase;

class PaEnrolledExceptionTest extends TestCase
{
    /** @var PaEnrolledException */
    private $paEnrolledException;

    /** @var \Magento\Framework\Phrase | \PHPUnit_Framework_MockObject_MockObject */
    private $phrase;

    /** @var int */
    private $httpCode;

    /** @var array */
    private $details;

    /** @var string */
    private $name;

    /** @var array */
    private $errors;

    /** @var string */
    private $stackTrace;

    protected function setUp()
    {
        $this->phrase = $this->createMock(\Magento\Framework\Phrase::class);
        $this->httpCode = \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST;
        $this->details = [];
        $this->name = '';
        $this->errors = null;
        $this->stackTrace = null;
        $this->paEnrolledException = new PaEnrolledException(
            $this->phrase,
            $this->httpCode,
            $this->details,
            $this->name,
            $this->errors,
            $this->stackTrace
        );
    }

    public function testCreation()
    {

        $this->assertInstanceOf(
            \CyberSource\ThreeDSecure\Gateway\PaEnrolledException::class,
            $this->paEnrolledException
        );
    }
}
