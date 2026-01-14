<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;


class CardDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }


    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $paymentDo = $this->subjectReader->readPayment($buildSubject);

        $result = [
            'cardTypeSelectionIndicator' => \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::CARD_TYPE_SELECTION_INDICATOR_BY_CARDHOLDER,
        ];

        $expDate = $paymentDo
            ->getPayment()
            ->getAdditionalInformation(\CyberSource\SecureAcceptance\Observer\DataAssignObserver::KEY_EXP_DATE);

        if (!$expDate) {
            return $result;
        }

        list($expMonth, $expYear) = array_pad(explode('-', $expDate ?? ''), 2, null);;

        if ($expMonth) {
            $result['card']['expirationMonth'] = $expMonth;
        }

        if ($expYear) {
            $result['card']['expirationYear'] = $expYear;
        }

        return $result;
    }
}
