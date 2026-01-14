<?php

namespace CyberSource\Core\Gateway\Validator;

use CyberSource\Core\Model\Logger; 

/**
 * Validates the status when HTTP Response Code is 201 for the transaction
 */
class RestStatusValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{

    const REJECT_PAYMENT_STATUS = ['INVALID_REQUEST', 'DECLINED', 'SERVER_ERROR','AUTHORIZED_RISK_DECLINED'];
    const RESPONSE_CODE_CREATED = 201;
    const  DM_REVIEW_STATUS = 'AUTHORIZED_PENDING_REVIEW';
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        Logger $logger
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject)
    {
        $result = $this->subjectReader->readResponse($validationSubject);
       
        if ($this->isSuccessfulTransaction($result)) {
            return $this->createResult(true, []);
        } 

        if($result['http_code'] == RestResponseCodeValidator::RESPONSE_CODE_CREATED && $result['status'] != self::DM_REVIEW_STATUS){
            if(in_array($result['status'], self::REJECT_PAYMENT_STATUS)){
                $error_message = __("Sorry your order could not be processed at this time. Reason is %1", $result['errorInformation']['reason']);
                $log = __("Transaction is declined due to %1", $result['errorInformation']['reason']);
                $this->logger->critical($log);
                return $this->createResult(false, [$error_message]);
            }
        }
        return $this->createResult(true);
    }


    /**
     * @param array $response
     * @return bool
     */
    private function isSuccessfulTransaction(array $result)
    {
        $status = $result['status'];
        $httpCode = $result['http_code'];
        return ($httpCode === self::RESPONSE_CODE_CREATED && $status === self::DM_REVIEW_STATUS);
    }
}
