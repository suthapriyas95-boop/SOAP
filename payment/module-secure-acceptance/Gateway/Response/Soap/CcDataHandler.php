<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;


class CcDataHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder
     */
    private $requestDataBuilder;
    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;
        const KEY_FLEX_MASKED_PAN = 'maskedPan';


    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder,
        \Magento\Framework\Url\DecoderInterface $urlDecoder
        
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->urlDecoder = $urlDecoder;

    }


    /**
     * Handles Cc data for flex microform
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!$this->config->isMicroform()) {
            return;
        }

        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        if ($payment->getMethod() !== \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE) {
            return;
        }

        $cardType = $response['card']->cardType ?? '';
        $decoded_transient_token = json_decode(json: $this->urlDecoder->decode(explode('.', $payment->getAdditionalInformation('flexJwt'))[1]));
        $card_value=$decoded_transient_token->content->paymentInformation->card->number->maskedValue;
        $payment->setAdditionalInformation(static::KEY_FLEX_MASKED_PAN, $card_value ?? null);
        $cardNumber = substr($card_value, 0, 6) . str_repeat('X', strlen($card_value) - 10) . substr($card_value, -4);
        $payment->setAdditionalInformation('cardNumber', $cardNumber);
        $payment->setAdditionalInformation('cardType', $cardType);

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return;
        }

        $payment->setCcType($this->requestDataBuilder->getCardType($cardType, true));
        $payment->setCcLast4(substr($card_value, -4));

        list($expMonth, $expYear) = explode('-', $payment->getAdditionalInformation(\CyberSource\SecureAcceptance\Observer\DataAssignObserver::KEY_EXP_DATE) ?? '');

        $payment->setCcExpMonth(ccExpMonth: $expMonth ?? null);
        $payment->setCcExpYear($expYear ?? null);


    }
}
