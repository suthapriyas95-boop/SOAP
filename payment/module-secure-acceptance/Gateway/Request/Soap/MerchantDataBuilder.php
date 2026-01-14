<?php


namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use \CyberSource\SecureAcceptance\Gateway\Config\Config;

class MerchantDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $gatewayConfig;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $gatewayConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * Builds Merchant Data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {

        $request = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $request['partnerSolutionID'] = \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID;
        $request['storeId'] = $paymentDO->getOrder()->getStoreId();
        $request['merchantID'] = $this->gatewayConfig->getValue(
            Config::KEY_MERCHANT_ID,
            $paymentDO->getOrder()->getStoreId()
        );
        $developerId = $this->gatewayConfig->getDeveloperId();
        if (!empty($developerId)) {
            $request['developerId'] = $developerId;
        }

        return $request;
    }
}
