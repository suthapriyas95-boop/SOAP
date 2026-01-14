<?php

namespace CyberSource\PayPal\Block\Customer;

use CyberSource\PayPal\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractTokenRenderer;

class VaultTokenRenderer extends AbstractTokenRenderer
{
    /**
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === Config::CODE;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return date('Y-m-d', strtotime($this->getTokenDetails()['date'] ?? ''));
    }

    /**
     * @return string
     */
    public function getMaskedId()
    {
        return '****' . substr($this->getToken()->getGatewayToken() ?? '', -4);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->getTokenDetails()['email'];
    }

    /**
     * @inheritdoc
     */
    public function getIconUrl()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getIconHeight()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getIconWidth()
    {
        return '';
    }
}
