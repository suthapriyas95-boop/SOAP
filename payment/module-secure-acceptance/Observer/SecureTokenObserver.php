<?php

namespace CyberSource\SecureAcceptance\Observer;

/**
 * Class SecureTokenObserver
 * @package CyberSource\SecureAcceptance\Observer
 */
class SecureTokenObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Model\SecureToken\Validator
     */
    private $validator;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serialize;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    private $actionFlag;

    /**
     * SecureTokenObserver constructor.
     *
     * @param \CyberSource\SecureAcceptance\Model\SecureToken\Validator $validator
     * @param \Magento\Framework\Serialize\SerializerInterface $serialize
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Model\SecureToken\Validator $validator,
        \Magento\Framework\Serialize\SerializerInterface $serialize,
        \Magento\Framework\App\ActionFlag $actionFlag
    ) {

        $this->validator = $validator;
        $this->serialize = $serialize;
        $this->actionFlag = $actionFlag;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\Action\Action $controller */
        $controller = $observer->getControllerAction();

        $token = $controller->getRequest()->getParam('secure_token');

        if ($this->validator->validate($token)) {
            return;
        }

        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);

        $controller->getResponse()->representJson(
            $this->serialize->serialize([
                'success' => false,
                'error' => true,
                'error_messages' => __('Invalid security token. Please refresh the page.'),
            ])
        );
    }
}
