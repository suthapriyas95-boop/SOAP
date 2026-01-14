<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Controller\Adminhtml\Taxclass;

class Index extends \Magento\Backend\App\Action
{
    protected $_resultPageFactory;

    protected $_resultPage;
    
    /**
     *
     * @var \Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory
     */
    protected $collectionTax;
    
    /**
     *
     * @var \Magento\Tax\Model\ClassModel
     */
    protected $modelTax;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory $collectionFactory
     * @param \Magento\Tax\Model\ClassModel $modelTax
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory $collectionFactory,
        \Magento\Tax\Model\ClassModel $modelTax,
        \Magento\Framework\Registry $registry
    ) {
    
        $this->collectionTax = $collectionFactory;
        $this->modelTax = $modelTax;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }
    
    public function execute()
    {
        //Call page factory to render layout and page content
        $this->_setPageData();
        return $this->getResultPage();
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CyberSource_Tax::tax_class');
    }

    protected function getResultPage()
    {
        if ($this->_resultPage === null) {
            $this->_resultPage = $this->_resultPageFactory->create();
        }
        return $this->_resultPage;
    }

    protected function _setPageData()
    {
        $resultPage = $this->getResultPage();
        $resultPage->setActiveMenu('CyberSource_Tax::tax_class');
        $resultPage->getConfig()->getTitle()->prepend((__('Tax Classes')));

        //Add bread crumb
        $resultPage->addBreadcrumb(__('Cybersource'), __('Cybersource'));
        $resultPage->addBreadcrumb(__('Cybersource'), __('Tax Classes'));

        return $this;
    }
}
