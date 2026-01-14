<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class SidBuilderTest extends TestCase
{
    /** @var SidBuilder */
    private $sidBuilder;

    /** @var \Magento\Framework\Session\SessionManagerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $sessionMock;

    /** @var \Magento\Framework\Encryption\EncryptorInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $encryptorMock;

    protected function setUp()
    {
        $this->sessionMock = $this->createMock(\Magento\Framework\Session\SessionManagerInterface::class);
        $this->encryptorMock = $this->createMock(\Magento\Framework\Encryption\EncryptorInterface::class);
        $this->sidBuilder = new SidBuilder(
            $this->sessionMock,
            $this->encryptorMock
        );
    }

    public function testBuild()
    {
        $this->sessionMock->method('getSessionId')->willReturn('some_id');
        $this->encryptorMock->method('encrypt')->with('some_id')->willReturn('some_encrypted_id');

        $this->assertEquals(
            ['merchant_secure_data2' => 'some_encrypted_id'],
            $this->sidBuilder->build([])
        );
    }

}
