<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\Gateway\Request\Rest;

class FluidDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{


    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;
    /**
     * @var string|null
     */
    private $additionalInformationKey;

    /**
     * FluidDataBuilder constructor.
     *
     * @param \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
     * @param string|null $additionalDataKey
     */
    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        $additionalDataKey = null
    ) {
        $this->subjectReader = $subjectReader;
        $this->additionalInformationKey = $additionalDataKey;
        if ($this->additionalInformationKey === null) {
            throw new \InvalidArgumentException('Additional data key must be provided');
        }
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $fluidDataValue = $payment->getAdditionalInformation($this->additionalInformationKey);

        return [
            'paymentInformation' => [
                'fluidData' => [
                    'value' => $fluidDataValue,
                ]
            ]
        ];
    }
}
