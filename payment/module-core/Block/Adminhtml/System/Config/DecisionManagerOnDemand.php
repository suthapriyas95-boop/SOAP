<?php


namespace CyberSource\Core\Block\Adminhtml\System\Config;


use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

class DecisionManagerOnDemand extends Field
{

    /**
     * @var string
     */
    protected $_template = 'CyberSource_Core::system/config/decisionmanagerondemand.phtml';

    /**
     * @var AbstractElement
     */
    private $element;



    /**
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        /** @var BlockInterface $button */
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData(
            [
                'id' => 'dm_update_button',
                'label' => __('Run')
            ]
        );

        return $button->toHtml();
    }

    /**
     * @return string
     */
    public function getUpdateUrl()
    {
        return $this->getUrl('cybersourceadmin/action/decisionmanagerondemand');
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->element = $element;
        return $this->_toHtml();
    }

}
