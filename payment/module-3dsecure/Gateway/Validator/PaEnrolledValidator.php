<?php

namespace CyberSource\ThreeDSecure\Gateway\Validator;

use CyberSource\ThreeDSecure\Gateway\PaEnrolledException;

class PaEnrolledValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    const CODE_PA_ENROLLED = 475;
    const KEY_ACESS_TOKEN = 'accessToken';
    const KEY_STEPUP_URL = 'stepUpUrl';
    const KEY_ACS_URL = 'acsURL';
    const KEY_PA_REQ = 'paReq';
    const KEY_PAYER_AUTH_ENROLL_REPLY = 'payerAuthEnrollReply';
    const KEY_AUTHENTICATION_TRANSACTION_ID = 'authenticationTransactionID';
    const KEY_PAYER_AUTH_ENROLL_TRANSACTION_ID = 'payer_auth_enroll_transaction_id';
    const KEY_SCA_REQUIRED = 'strong_customer_authentication_required';

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface
     */
    private $builder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $session;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Payment\Gateway\Request\BuilderInterface $builder,
        \Magento\Checkout\Model\Session $session
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->builder = $builder;
        $this->session = $session;
    }

    /**
     * Handles response code 475 for PA enrolled cards
     *
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     * @throws PaEnrolledException
     */
    public function validate(array $validationSubject)
    {
        $response = $this->subjectReader->readResponse($validationSubject);
        $code = $response[\CyberSource\SecureAcceptance\Gateway\Validator\SoapReasonCodeValidator::RESULT_CODE] ?? null;

        if ($code !== self::CODE_PA_ENROLLED && $code !== 478) {
            return $this->createResult(true);
        }

        $paException = null;
        $payment = ($code == 478) ? $this->session->getQuote()->getPayment() : null;

        if($code == 475){
            $payerAuthEnrollReply = (array)$response[self::KEY_PAYER_AUTH_ENROLL_REPLY];
            $paException = new PaEnrolledException(
                __('Payer Authentication is required.'),
                $code,
                PaEnrolledException::HTTP_BAD_REQUEST,
                [
                    'code' => $code,
                    'cca' => [
                        'AcsUrl' => $payerAuthEnrollReply[self::KEY_ACS_URL],
                        'Payload' => $payerAuthEnrollReply[self::KEY_PA_REQ],
                        'accessToken' =>$payerAuthEnrollReply[self::KEY_ACESS_TOKEN],
                        'stepUpUrl' =>$payerAuthEnrollReply[self::KEY_STEPUP_URL],
                    ],
                    'order' => array_replace_recursive(
                        [
                            'OrderDetails' => [
                                'TransactionId' => $payerAuthEnrollReply[self::KEY_AUTHENTICATION_TRANSACTION_ID],
                            ]
                        ],
                        $this->builder->build($validationSubject)
                    ),
                ]
            );
        }
        if($code == 475){
            $payerAuthEnrollReply = (array)$response[self::KEY_PAYER_AUTH_ENROLL_REPLY];
            $this->builder->build($validationSubject);
        }      
        else if($code == 478 &&
            $payment->getAdditionalInformation(self::KEY_SCA_REQUIRED)
        ) {
            $payment->setAdditionalInformation(self::KEY_SCA_REQUIRED, false);
            $paException = new PaEnrolledException(
                __('We were unable to complete your order. Please try again later or try with a different payment method.'),
                10000, 
                PaEnrolledException::HTTP_BAD_REQUEST
            );
        }
        else if($code == 478){
            $payment->setAdditionalInformation(self::KEY_SCA_REQUIRED, true);
            $paException = new PaEnrolledException(
                __('Strong Customer Authentication is required.'),
                $code,
                PaEnrolledException::HTTP_BAD_REQUEST
            );
        }

        if($paException)
            throw $paException;
    }
}
