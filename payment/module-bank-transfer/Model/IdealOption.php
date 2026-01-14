<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model;

class IdealOption extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('CyberSource\BankTransfer\Model\ResourceModel\IdealOption');
    }
}
