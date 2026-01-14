<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class PaBuilderTest extends TestCase
{
    /** @var PaBuilder */
    private $paBuilder;

    protected function setUp()
    {
        $this->paBuilder = new PaBuilder();
    }

    public function testBuild()
    {
        $this->assertEquals(['payer_auth_enroll_service_run' => 'true'], $this->paBuilder->build([]));
    }
}
