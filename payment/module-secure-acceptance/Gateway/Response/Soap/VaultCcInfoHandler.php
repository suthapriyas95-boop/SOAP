<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;


class VaultCcInfoHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{


    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder
     */
    private $requestDataBuilder;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder

    ) {
        $this->subjectReader = $subjectReader;
        $this->serializer = $serializer;
        $this->requestDataBuilder = $requestDataBuilder;
    }

    /**
     * Stores CC information for vault payments
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {

        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        if (!$payment->getExtensionAttributes()) {
            return;
        }

        if (!$vaultToken = $payment->getExtensionAttributes()->getVaultPaymentToken()) {
            return;
        }

        if (!$details = $vaultToken->getTokenDetails()) {
            return;
        }

        try {
            $details = $this->serializer->unserialize($details);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        if (!empty($details['type'])) {
            $payment->setCcType($details['type']);
            $payment->setAdditionalInformation(
                'cardType',
                $this->requestDataBuilder->getCardType($details['type'])
            );
        }

        if (!empty($details['maskedCC'])) {

            $maskedPan = str_replace('-', '', $details['maskedCC']) ?? '';

            $payment->setAdditionalInformation(
                'cardNumber',
                 str_repeat('x', strlen($maskedPan) - 4)
                . substr($maskedPan, -4)
            );

            $payment->setCcLast4(substr($maskedPan, -4));
        }

        if (!empty($details['expirationDate'])) {
            list($expMonth, $expYear) = explode('/', $details['expirationDate'] ?? '');
            $payment->setCcExpYear($expYear);
            $payment->setCcExpMonth($expMonth);
        }


    }
}
