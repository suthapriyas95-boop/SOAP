<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Controller\Adminhtml\Taxclass;

class Delete extends \CyberSource\Tax\Controller\Adminhtml\Taxclass\Index
{
    
   /**
    * @return void
    */
    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            $model = $this->modelTax->load($id);
            // Check this news exists or not
            if (!$model->getId()) {
                $this->messageManager->addError(__('This tax class no longer exists.'));
            } else {
                try {
                   // Delete news
                    $model->delete();
                    $this->messageManager->addSuccess(__('The tax class has been deleted.'));
 
                   // Redirect to grid page
                    $this->_redirect('*/*/');
                    return;
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());
                    $this->_redirect('*/*/edit', ['id' => $model->getId()]);
                }
            }
        }
    }
}
