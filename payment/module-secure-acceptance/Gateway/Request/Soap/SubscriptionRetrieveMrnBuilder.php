<?php

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

class SubscriptionRetrieveMrnBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    public function __construct(\Magento\Framework\Math\Random $random)
    {
        $this->random = $random;
    }

    /**
     * Builds MRN for subscription retrieve request
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        return [
            'merchantReferenceCode' => $this->random->getUniqueHash('subscription_request_')
        ];
    }
}
