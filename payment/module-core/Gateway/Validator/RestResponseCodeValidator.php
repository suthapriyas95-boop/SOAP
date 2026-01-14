<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Validator;


class RestResponseCodeValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{

    const RESPONSE_CODE_OK = 200;
    const RESPONSE_CODE_CREATED = 201;
    const RESPONSE_CODE_NOT_FOUND = 404;
    const RESPONSE_CODE_UNAUTHORIZED = 401;
    const RESPONSE_CODE_BAD_REQUEST = 400;

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;
    /**
     * @var int
     */
    private $validResponseCode;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        $validResponseCode = self::RESPONSE_CODE_OK
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->validResponseCode = $validResponseCode;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject)
    {

        $result = $this->subjectReader->readResponse($validationSubject);

        $httpCode = $result['http_code'] ?? static::RESPONSE_CODE_BAD_REQUEST;

        if ($httpCode == static::RESPONSE_CODE_NOT_FOUND) {
            throw new NotFoundException('No data found.');
        }

        if ($httpCode != $this->validResponseCode) {
            return $this->createResult(false, [
                __($result['errorInformation']['message'] ?? 'Gateway rejected the transaction.')
            ]);
        }

        return $this->createResult(true);
    }
}
