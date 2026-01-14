<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Block\Checkout;

use Magento\Vault\Model\CustomerTokenManagement;

class Billing extends \Magento\Multishipping\Block\Checkout\Billing
{
    /**
     * @var CustomerTokenManagement
     */
    private $customerTokenManagement;

    /**
     * Billing constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory
     * @param \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Payment\Model\Method\SpecificationInterface $paymentSpecification
     * @param CustomerTokenManagement $customerTokenManagement
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory,
        \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Payment\Model\Method\SpecificationInterface $paymentSpecification,
        CustomerTokenManagement $customerTokenManagement,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $paymentHelper,
            $methodSpecificationFactory,
            $multishipping,
            $checkoutSession,
            $paymentSpecification,
            $data
        );

        $this->customerTokenManagement = $customerTokenManagement;
    }

    public function getCustomerTokens()
    {
        $customerPaymentTokens = $this->customerTokenManagement->getCustomerSessionTokens();
        $data = [];
        if (!empty($customerPaymentTokens)) {
            /** @var \Magento\Vault\Model\PaymentToken $token */
            foreach ($customerPaymentTokens as $paymentToken) {
                $tokenDetails = json_decode($paymentToken->getTokenDetails());

                if ($paymentToken->getPaymentMethodCode() !== \CyberSource\Core\Model\Config::CODE) {
                    continue;
                }

                $data[] = [
                    'public_hash' => $paymentToken->getPublicHash(),
                    'title' => $tokenDetails->maskedCC,
                    'card_type' => strtolower($tokenDetails->type ?? ''),
                    'expiry_date' => $tokenDetails->expirationDate
                ];
            }
        }

        return $data;
    }

    public function getCreateTokenUrl()
    {
        return $this->_urlBuilder->getUrl('cybersource/index/createtoken');
    }

    public function getCcTypes()
    {
        return [
            'VI' => ['code' => '001', 'name' => 'Visa'],
            'MC' => ['code' => '002', 'name' => 'MasterCard'],
            'AE' => ['code' => '003', 'name' => 'American Express'],
            'DI' => ['code' => '004', 'name' => 'Discover'],
            'JCB' => ['code' => '007', 'name' => 'JCB'],
            'MI' => ['code' => '042', 'name' => 'Maestro International'],
            'DN' => ['code' => '005', 'name' => 'Diners Club'],
        ];
    }
}
