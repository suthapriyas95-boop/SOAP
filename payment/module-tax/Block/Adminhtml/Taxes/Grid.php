<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\Tax\Block\Adminhtml\Taxes;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     *
     * @var \Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory
     */
    protected $collectionTax;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionTax = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('taxGrid');
        $this->setDefaultSort('class_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * @return $this
     */
    public function _prepareCollection()
    {
        $collection = $this->collectionTax->create();
        
        $this->setCollection($collection);

        parent::_prepareCollection();
        
        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function _prepareColumns()
    {
        $this->addColumn(
            'class_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'class_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'name'=>'class_id'
            ]
        );
        $this->addColumn(
            'class_name',
            [
                'header' => __('Class Name'),
                'index' => 'class_name',
                'name'=>'class_name'
            ]
        );
        $this->addColumn(
            'class_type',
            [
                'header' => __('Class Type'),
                'index' => 'class_type',
                'name'=>'class_type'
            ]
        );
        $this->addColumn(
            'edit',
            [
                'header' => __('Edit'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => '*/*/edit'
                        ],
                        'field' => 'class_id'
                    ]
                ],
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    public function _prepareMassaction()
    {
        return $this;
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('cybersourcetax/*/grid', ['_current' => true]);
    }

    /**
     * @param \Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'cybersourcetax/*/edit',
            ['id' => $row->getId()]
        );
    }
}
