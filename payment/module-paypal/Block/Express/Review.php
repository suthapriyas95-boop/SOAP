<?php

namespace CyberSource\PayPal\Block\Express;

class Review extends \Magento\Paypal\Block\Express\Review
{
    /**
     * Paypal controller path
     *
     * @var string
     */
    protected $_controllerPath = 'cybersourcepaypal/express';

    public function canEditShippingMethod()
    {
        return true;
    }
}
