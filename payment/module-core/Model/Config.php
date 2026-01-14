<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\Core\Model;

/**
 * Class Config
 * @package CyberSource\Core\Model
 * @codeCoverageIgnore
 */
class Config extends AbstractGatewayConfig
{
    const CODE = 'chcybersource';

    const KEY_REST_SHARED_KEY_ID = 'rest_key_id';
    const KEY_REST_SHARED_KEY_VALUE = 'rest_key_value';
    const KEY_OVERRIDE_ERROR_PAGE_ROUTE = 'override_error_page_route';

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getRestKeyId($storeId = null)
    {
        return $this->getValue(self::KEY_REST_SHARED_KEY_ID, $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getRestKeyValue($storeId = null)
    {
        return $this->getValue(self::KEY_REST_SHARED_KEY_VALUE, $storeId);
    }

    public function getOverrideErrorPageRoute($storeId = null)
    {
        return $this->getValue(static::KEY_OVERRIDE_ERROR_PAGE_ROUTE, $storeId);
    }
}
