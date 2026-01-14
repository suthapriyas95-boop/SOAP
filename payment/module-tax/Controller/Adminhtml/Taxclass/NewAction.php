<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
 
namespace CyberSource\Tax\Controller\Adminhtml\Taxclass;
  
class NewAction extends \CyberSource\Tax\Controller\Adminhtml\Taxclass\Index
{
   /**
    * Create new news action
    *
    * @return void
    */
    public function execute()
    {
        $this->_forward('edit');
    }
}
