<?php

namespace CyberSource\AccountUpdater\Block\Adminhtml\System\Config;

use Magento\Framework\Stdlib\DateTime;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Update extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    protected $_template = 'CyberSource_AccountUpdater::system/config/update.phtml';

    /**
     * @var AbstractElement
     */
    private $element;

    /**
     * @return string
     */
    public function getDateWidgetHtml()
    {
        $dataInit = 'data-mage-init="' . htmlspecialchars(
            json_encode(
                [
                        'calendar' => [
                            'dateFormat' => DateTime::DATE_INTERNAL_FORMAT,
                            'showsTime' => false,
                            'buttonImage' => $this->element->getImage(),
                            'buttonText' => 'Select Date',
                            'disabled' => $this->element->getDisabled()
                        ],
                    ]
            )
        ) . '"';

        $html = sprintf(
            '<input type="text" name="%s" id="au_update_date" class="%s" value="" %s style="width: auto;"/>',
            $this->element->getName(),
            'admin__control-text  input-text',
            $dataInit
        );

        return $html;
    }

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
                'id' => 'au_update_button',
                'label' => __('Update')
            ]
        );

        return $button->toHtml();
    }

    /**
     * @return string
     */
    public function getUpdateUrl()
    {
        return $this->getUrl('csau/action/update');
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
