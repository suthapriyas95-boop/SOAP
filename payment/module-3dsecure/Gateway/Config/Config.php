<?php
/**
 * Copyright © 2018 CollinsHarper. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ThreeDSecure\Gateway\Config;

/**
 * Class Config
 */
class Config extends \CyberSource\Core\Model\AbstractGatewayConfig
{

    const KEY_IS_ACTIVE = 'active_3ds';
    const KEY_ENABLED_CARDS = 'card_types_3ds';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($scopeConfig, \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE);
    }

    public function isEnabled()
    {
        return $this->getValue(self::KEY_IS_ACTIVE);
    }

    public function getEnabledCards($storeId = null)
    {
        return explode(',', $this->getValue(self::KEY_ENABLED_CARDS, $storeId) ?? '');
    }
}
