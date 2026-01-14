<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\Tax\Controller\Adminhtml\Taxclass;
  
class Edit extends \CyberSource\Tax\Controller\Adminhtml\Taxclass\Index
{
    
   /**
    * @return void
    */
    public function execute()
    {
        $id = $this->getRequest()->getParam('class_id');
        if ($id) {
            $this->modelTax->load($id);
            if (!$this->modelTax->getId()) {
                $this->messageManager->addError(__('This tax class no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        
        $this->_coreRegistry->register('tax_class_model', $this->modelTax);
 
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('CyberSource_Tax::cybersourcetax_tax');
        $resultPage->getConfig()->getTitle()->prepend(__('Tax Class'));
        return $resultPage;
    }
}
