<?php
/**
 * Copyright © 2018 CollinsHarper. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ApplePay\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class CaptureRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * Builds request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $request = $this->requestDataBuilder->buildCaptureRequestData(
            $this->getValidPaymentInstance($buildSubject),
            $this->subjectReader->readAmount($buildSubject),
            $buildSubject
        );

        return (array) $request;
    }
}
