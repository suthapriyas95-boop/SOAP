<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Gateway\Response\Sop;

class CcDataHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder
     */
    private $requestDataBuilder;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder
    ) {
        $this->subjectReader = $subjectReader;
        $this->requestDataBuilder = $requestDataBuilder;
    }

    /**
     * Handles Credit card type and other related info from SA response
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {

        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $maskedPan = $response[\CyberSource\SecureAcceptance\Gateway\Response\AbstractResponseHandler::CARD_NUMBER] ?? '';
        $payment->setAdditionalInformation('cardNumber',
            substr($maskedPan, 0, 6) . str_repeat('x', strlen($maskedPan) - 10) . substr($maskedPan, -4));
        $payment->setAdditionalInformation('cardType', $response['req_card_type']);

        if (!$payment instanceof \Magento\Quote\Model\Quote\Payment) {
            return;
        }

        $payment->setCcType($this->requestDataBuilder->getCardType($response['req_card_type'], true));
        $payment->setCcLast4(substr($maskedPan, -4));

        list($expMonth, $expYear) = explode('-', $response['req_card_expiry_date'] ?? '');

        $payment->setCcExpMonth($expMonth);
        $payment->setCcExpYear($expYear);

    }
}
