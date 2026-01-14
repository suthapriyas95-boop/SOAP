<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Gateway\Validator\Sop;

class QuoteValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    /**
     * Performs validation of quote_id returned from cybersource
     *
     * @param array $validationSubject
     *
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = $this->subjectReader->readResponse($validationSubject);
        $paymentDO = $this->subjectReader->readPayment($validationSubject);

        $responseQuoteId = $response['req_' . \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_QUOTE_ID] ?? null;

        $isValid = true;
        $errorMessages = [];

        if (is_null($paymentDO->getOrder()->getId()) || $paymentDO->getOrder()->getId() != $responseQuoteId) {
            $isValid = false;
            $errorMessages[] = __('Incorrect Quote ID');
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
