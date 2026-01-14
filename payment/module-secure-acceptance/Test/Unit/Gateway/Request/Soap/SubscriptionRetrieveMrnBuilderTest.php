<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class SubscriptionRetrieveMrnBuilderTest extends TestCase
{
    /** @var SubscriptionRetrieveMrnBuilder */
    private $subscriptionRetrieveMrnBuilder;

    /** @var \Magento\Framework\Math\Random | \PHPUnit_Framework_MockObject_MockObject */
    private $randomMock;

    protected function setUp()
    {
        $this->randomMock = $this->createMock(\Magento\Framework\Math\Random::class);
        $this->subscriptionRetrieveMrnBuilder = new SubscriptionRetrieveMrnBuilder(
            $this->randomMock
        );
    }

    public function testBuild()
    {

        $this->randomMock
            ->method('getUniqueHash')
            ->with('subscription_request_')
            ->willReturn('subscription_request_13232')
        ;

        $this->assertEquals(
            ['merchantReferenceCode' => 'subscription_request_13232'],
            $this->subscriptionRetrieveMrnBuilder->build([])
        );
    }
}
