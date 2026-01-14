<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model\Checkout;;

interface PaymentFailureRouteProviderInterface
{

    /**
     * Returns the route path that the customer will be redirected on checkout payment failures
     *
     * @return string Failure page route path
     */
    public function getFailureRoutePath();

}
