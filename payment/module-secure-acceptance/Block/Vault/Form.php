<?php

namespace CyberSource\SecureAcceptance\Block\Vault;

class Form extends \Magento\Vault\Block\Form
{



    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Vault\Model\Ui\Adminhtml\TokensConfigProvider $tokensConfigProvider,
        \Magento\Payment\Model\CcConfigProvider $ccConfigProvider,
        array $data = []
    ) {
        parent::__construct($context, $tokensConfigProvider, $ccConfigProvider, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->createCvnBlock();
        return $this;
    }


    public function setMethod(\Magento\Payment\Model\MethodInterface $method)
    {
        /** @var \Magento\Payment\Block\Form $cvnBlock */
        if ($cvnBlock = $this->getChildBlock('field_cvn')) {
            $cvnBlock->setMethod($method);
        }

        return parent::setMethod($method);
    }

    private function createCvnBlock()
    {
        $this->addChild(
            'field_cvn',
            \Magento\Payment\Block\Form::class,
            [
                'template' => 'CyberSource_SecureAcceptance::vault/cvn.phtml',
            ]
        );
    }
}
