<?php

namespace CyberSource\ThreeDSecure\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Session\StorageInterface;


class ReturnController extends Action implements CsrfAwareActionInterface
{
    private $sessionStorage;
    public function __construct(StorageInterface $sessionStorage,
        Context $context
    ) {
        parent::__construct($context);
        $this->sessionStorage = $sessionStorage;
    }

    public function execute()
    {      
        sleep(20);    
        $transId = $this->getRequest()->getParam('TransactionId');
        $this->sessionStorage->setData(['json_data', $transId]);
        return null;
    }
    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
