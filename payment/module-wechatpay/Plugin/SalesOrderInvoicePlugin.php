<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Plugin;

class SalesOrderInvoicePlugin
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $subject
     * @param $result
     * @return bool
     */
    public function afterCanCapture(
        \Magento\Sales\Model\Order\Invoice $subject,
        $result
    ) {
        $method = $subject->getOrder()->getPayment()->getMethod();
        if ($method == \CyberSource\WeChatPay\Model\Ui\ConfigProvider::CODE) {
            return false;
        }

        return $result;
    }
}
