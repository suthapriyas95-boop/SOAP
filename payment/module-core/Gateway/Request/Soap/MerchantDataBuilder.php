<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Soap;

class MerchantDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\Core\Model\AbstractGatewayConfig
     */
    private $config;

    /**
     * @param \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
     * @param \CyberSource\Core\Model\AbstractGatewayConfig $config
     */
    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\Core\Model\AbstractGatewayConfig $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $request = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $storeId = $paymentDO->getOrder()->getStoreId();

        $request['partnerSolutionID'] = \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID;
        $request['merchantID'] = $this->config->getMerchantId($storeId);
        $request['storeId'] = $storeId;

        if ($developerId = $this->config->getDeveloperId()) {
            $request['developerId'] = $developerId;
        }

        return $request;
    }
}
