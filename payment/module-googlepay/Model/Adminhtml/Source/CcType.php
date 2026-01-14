<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Model\Adminhtml\Source;

/**
 * Class CcType
 * @package CyberSource\GooglePay\Model\Adminhtml\Source
 * @codeCoverageIgnore
 */
class CcType extends \Magento\Payment\Model\Source\Cctype
{

    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return [
            'AE',
            'DI',
            'JCB',
            'MC',
            'VI',
        ];
    }
}
