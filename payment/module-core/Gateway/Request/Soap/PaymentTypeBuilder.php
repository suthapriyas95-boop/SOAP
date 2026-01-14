<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Soap;

class PaymentTypeBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var string
     */
    private $paymentCode;

    /**
     * @param string $paymentCode
     */
    public function __construct(string $paymentCode)
    {
        $this->paymentCode = $paymentCode;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        return ['apPaymentType' => $this->paymentCode];
    }
}
