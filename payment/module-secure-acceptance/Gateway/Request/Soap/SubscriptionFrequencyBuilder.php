<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;


class SubscriptionFrequencyBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        return [
            'recurringSubscriptionInfo' => [
                'frequency' => 'on-demand',
            ],
            'subsequentAuthFirst' => 'true'
        ];
    }
}
