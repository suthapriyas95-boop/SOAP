<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

class BuilderStrategy implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var PayerAuthEnrollBuilder
     */
    private $enrollBuilder;

    /**
     * @var PayerAuthValidateBuilder
     */
    private $validateBuilder;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\ThreeDSecure\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var bool
     */
    private $isAdminhtml;

    public function __construct(
        PayerAuthEnrollBuilder $enrollBuilder,
        PayerAuthValidateBuilder $validateBuilder,
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder,
        \CyberSource\ThreeDSecure\Gateway\Config\Config $config,
        $isAdminhtml = false
    ) {
        $this->enrollBuilder = $enrollBuilder;
        $this->validateBuilder = $validateBuilder;
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->isAdminhtml = $isAdminhtml;
    }

    /**
     * Strategy method to determine whether paEnroll or paValidate or empty request should be built
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!$this->config->isEnabled() || $this->isAdminhtml) {
            return [];
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        /** @var \Magento\Quote\Api\Data\PaymentInterface $payment */
        $extensionAttributes = $payment->getExtensionAttributes();

        $cardType = $this->requestDataBuilder->getCardType($payment->getAdditionalInformation('cardType'), true);

        if (!in_array($cardType, $this->config->getEnabledCards())) {
            return [];
        }

         if ($extensionAttributes && $extensionAttributes->getCcaResponse()) {
             return $this->validateBuilder->build($buildSubject);
         }
        
        return $this->enrollBuilder->build($buildSubject);

    }
}
