<?php

namespace CyberSource\SecureAcceptance\Gateway\Validator;

use CyberSource\Core\Helper\ReasonCodeHandler;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class ResponseCodeValidator extends AbstractValidator
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
        $cyberSourceResponse = $this->getCyberSourceResponse($response);
        $reasonCode = (int) $cyberSourceResponse[self::RESULT_CODE];
        return ($reasonCode === self::APPROVED || $reasonCode === self::DM_REVIEW);
    }

    /**
     * @param array $response
     * @return \Magento\Framework\Phrase
     */
    private function getExceptionReasonCode(array $response)
    {
        $cyberSourceResponse = $this->getCyberSourceResponse($response);
        $reasonCode = (int) $cyberSourceResponse[self::RESULT_CODE];
        if (ReasonCodeHandler::isError($reasonCode) || ReasonCodeHandler::isDeclined($reasonCode)) {
            return ReasonCodeHandler::getMessageForCode($reasonCode);
        }

        return __('Sorry your order could not be processed at this time, error code: ' . $reasonCode);
    }

    private function getCyberSourceResponse(array $response)
    {
        return $response;
    }
}
