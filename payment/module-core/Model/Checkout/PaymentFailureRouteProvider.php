<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\Model\Checkout;


class PaymentFailureRouteProvider implements PaymentFailureRouteProviderInterface
{

    /**
     * @var \CyberSource\Core\Model\Config
     */
    private $config;

    /**
     * @var string
     */
    private $defaultPaymentFailureRoute;

    /**
     * PaymentFailureRouteProvider constructor.
     *
     * @param \CyberSource\Core\Model\Config $config
     * @param string $defaultPaymentFailureRoute
     */
    public function __construct(
        \CyberSource\Core\Model\Config $config,
        $defaultPaymentFailureRoute = 'checkout/cart'
    ) {
        $this->defaultPaymentFailureRoute = $defaultPaymentFailureRoute;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getFailureRoutePath()
    {

        $overriddenRoute = trim($this->config->getOverrideErrorPageRoute() ?? '');

        return $overriddenRoute ?: $this->defaultPaymentFailureRoute;

    }
}
