<?php

namespace CyberSource\PayPal\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class VaultReasonCodeValidator extends AbstractValidator
{
    const RESULT_CODE = 'reasonCode';
    const CODE_APPROVED = 100;
    const CODE_DM_REVIEW = 480;

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response is empty');
        }

        if ($this->isSuccessfulTransaction($validationSubject['response'])) {
            return $this->createResult(true, []);
        }

        return $this->createResult(false, [__('Gateway rejected the transaction.')]);
    }

    /**
     * @param array $response
     * @return bool
     */
    private function isSuccessfulTransaction(array $response)
    {
        if (empty($response[self::RESULT_CODE])) {
            return false;
        }

        return in_array($response[self::RESULT_CODE], [self::CODE_APPROVED, self::CODE_DM_REVIEW]);
    }
}
