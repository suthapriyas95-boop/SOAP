<?php


namespace CyberSource\Core\Gateway\ErrorMapper;

class ConfigurableMapper implements \Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface
{

    /**
     * @var \CyberSource\Core\Model\Config
     */
    private $config;

    public function __construct(
        \CyberSource\Core\Model\Config $config
    ) {

        $this->config = $config;
    }


    /**
     * Returns customized error message by provided code.
     * If message not found `null` will be returned.
     *
     * @param string $code
     * @return \Magento\Framework\Phrase|null
     */
    public function getMessage(string $code)
    {

        if (!$this->config->getValue(\CyberSource\Core\Model\AbstractGatewayConfig::KEY_SHOW_EXACT_ERROR)) {
            return __('Transaction has been declined. Please try again later.');
        }
        return __($code);
    }
}
