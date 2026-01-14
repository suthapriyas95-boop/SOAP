<?php

namespace CyberSource\SecureAcceptance\Gateway\Validator;

use CyberSource\Core\Helper\ReasonCodeHandler;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class VaultReasonCodeValidator extends AbstractValidator
{
    const RESULT_CODE = 'reason_code';
    const APPROVED = 100;
    const DM_REVIEW = 480;

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        if ($this->isSuccessfulTransaction($validationSubject['response'])) {
            return $this->createResult(true, []);
        } else {
            $exceptionMessage = $this->getExceptionReasonCode($validationSubject['response']);
            return $this->createResult(false, [$exceptionMessage]);
        }
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
        $reasonCode = (int) $response[self::RESULT_CODE];
        return ($reasonCode === self::APPROVED || $reasonCode === self::DM_REVIEW);
    }

    /**
     * @param array $response
     * @return \Magento\Framework\Phrase
     */
    private function getExceptionReasonCode(array $response)
    {

        if (empty($response[self::RESULT_CODE])) {
            return __('Sorry your order could not be processed at this time.');
        }

        $reasonCode = (int) $response[self::RESULT_CODE];
        if (ReasonCodeHandler::isError($reasonCode) || ReasonCodeHandler::isDeclined($reasonCode)) {
            return ReasonCodeHandler::getMessageForCode($reasonCode);
        }

        return __('Sorry your order could not be processed at this time, error code: %1', $reasonCode);
    }
}
