<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Soap;

class OrderMrnBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @param \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        return ['merchantReferenceCode' => $paymentDO->getOrder()->getOrderIncrementId()];
    }
}
