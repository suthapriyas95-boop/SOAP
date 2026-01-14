<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\Tax\Block\Adminhtml\Taxes\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @return $this
     */
    public function _prepareForm()
    {
        $id = $this->getRequest()->getParam('class_id');
        if ($id) {
            $model = $this->_coreRegistry->registry('tax_class_model');
            $model->load($id);
            $data = $model->getData();
        }
        
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'    => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post'
                ]
            ]
        );
        
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Tax Class'), 'class' => 'fieldset-wide']
        );
        
        $fieldset->addField(
            'class_id',
            'hidden',
            ['name' => 'class_id']
        );
        
        $fieldset->addField(
            'class_name',
            'text',
            [
                'name' => 'class_name',
                'label' => __('Tax Class Code'),
                'title' => __('Tax Class Code'),
                'required' => true
            ]
        );
        
        $fieldset->addField(
            'class_type',
            'select',
            [
                'name' => 'class_type',
                'label' => __('Tax Class Type'),
                'title' => __('Tax Class Type'),
                'required' => true,
                'options' => ['PRODUCT' => __('Product'), 'CUSTOMER' => __('Customer')]
            ]
        );
        
        $form->setUseContainer(true);
        
        if (!empty($data)) {
            $form->setValues($data);
        }
        
        $this->setForm($form);
 
        return parent::_prepareForm();
    }
}
