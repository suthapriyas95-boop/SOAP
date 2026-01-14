<?php

namespace CyberSource\Core\Gateway\Response;

class RawDetailsHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    const EXCLUDE_KEYS = [
        'accountNumber',
        'bankTransitNumber',
        'req_card_number',
        'req_card_expiry_date',
        'signed_field_names',
        'signature',
        'utf8',
    ];

    const INCLUDE_KEYS = [
        'req_transaction_uuid'
    ];

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDo = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);

        $payment = $paymentDo->getPayment();

        $reqData = $this->filterReqParams($response);

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return;
        }

        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $reqData
        );
    }


    private function filterReqParams($params)
    {

        return array_filter(
            $params,
            function ($key) {
                return !in_array($key, self::EXCLUDE_KEYS)
                    && (
                        in_array($key, self::INCLUDE_KEYS)
                        || stripos($key, 'req_') !== 0
                    );
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
