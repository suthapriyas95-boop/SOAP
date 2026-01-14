<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Model;

use CyberSource\WeChatPay\Gateway\Config\Config;

class StatusCheckMessageMapper
{
    private $messageMap = [
        Config::PAYMENT_STATUS_ABANDONED => 'Payment not processed due to user inactivity. Please try again or select an alternate payment method.',
        Config::PAYMENT_STATUS_FAILED => 'Your payment could not be processed and you have not been charged. You will now be redirected to your shopping cart.',
        Config::PAYMENT_STATUS_PENDING => 'Your payment is being processed. Please do not close or refresh the browser window.',
        Config::PAYMENT_STATUS_SETTLED => 'Your payment was successfully processed. Please wait while we redirect you to your order confirmation.',
    ];

    public function getMessage($wcpStatus)
    {
        return __($this->messageMap[$wcpStatus] ?? 'Something wrong.');
    }
}
