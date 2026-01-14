<?php

namespace CyberSource\SecureAcceptance\Gateway\Helper;

use Magento\Payment\Gateway\Helper;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class SubjectReader
 */
class SubjectReader
{
    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject)
    {
        return Helper\SubjectReader::readPayment($subject);
    }

    public function readAmount(array $subject)
    {
        return Helper\SubjectReader::readAmount($subject);
    }

    public function readResponse(array $subject)
    {
        return Helper\SubjectReader::readResponse($subject);
    }

    public function readStateObject(array $subject)
    {
        return Helper\SubjectReader::readStateObject($subject);
    }
}
