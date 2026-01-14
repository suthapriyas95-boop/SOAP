<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Validator;


class ConversionDetailsValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject)
    {

        $result = $this->subjectReader->readResponse($validationSubject);

        if (!isset($result['conversionDetails']) || !is_array($result['conversionDetails'])) {
            return $this->createResult(false, ['conversionDetails is not set.']);
        }

        return $this->createResult(true);
    }
}
