<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Controller\Adminhtml\Taxclass;

class Save extends \CyberSource\Tax\Controller\Adminhtml\Taxclass\Index
{

    /**
     * @return void
     */
    public function execute()
    {
        $isPost = $this->getRequest()->getPost();

        if ($isPost) {
            $id = $this->getRequest()->getParam('class_id');
            if ($id) {
                $this->modelTax->load($id);
            }
            $this->modelTax->setData('class_name', $this->getRequest()->getParam('class_name'));
            $this->modelTax->setData('class_type', $this->getRequest()->getParam('class_type'));

            try {
                // Save news
                $this->modelTax->save();

                // Display success message
                $this->messageManager->addSuccess(__('The tax class has been saved.'));

                // Check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $this->modelTax->getId(), '_current' => true]);
                    return;
                }

                // Go to grid page
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
    }
}
