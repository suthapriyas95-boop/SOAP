<?php
/**
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Request;

use Magento\Checkout\Model\Session;

class MerchantDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config
     */
    private $config;
	
	/**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @param \CyberSource\WeChatPay\Gateway\Helper\SubjectReader $subjectReader
     * @param \CyberSource\WeChatPay\Gateway\Config\Config $config
	 * @param \Magento\Checkout\Model\Session
     */
    public function __construct(
        \CyberSource\WeChatPay\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\WeChatPay\Gateway\Config\Config $config,
        Session $checkoutSession
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
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
        $request['storeId'] = $storeId;
        $fingerprintId = $this->checkoutSession->getData('fingerprint_id');
        if (!empty($fingerprintId)) {
            $request['device_fingerprint_id'] = $fingerprintId;
        }

        if ($developerId = $this->config->getDeveloperId()) {
            $request['developerId'] = $developerId;
        }

        return $request;
    }
}
