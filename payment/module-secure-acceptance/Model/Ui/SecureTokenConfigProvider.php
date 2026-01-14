<?php

namespace CyberSource\SecureAcceptance\Model\Ui;
 
use Magento\Framework\App\RequestInterface;
 
/**
* Class SecureTokenConfigProvider
* @package CyberSource\SecureAcceptance\Model\Ui
*/
class SecureTokenConfigProvider
{
    /**
     * @var \CyberSource\SecureAcceptance\Model\SecureToken\Generator
     */
    private $generator;
 
    /**
     * @var RequestInterface
     */
    private $request;
 
    /**
     * SecureTokenConfigProvider constructor.
     *
     * @param \CyberSource\SecureAcceptance\Model\SecureToken\Generator $generator
     * @param RequestInterface $request
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Model\SecureToken\Generator $generator,
        RequestInterface $request
    ) {
        $this->generator = $generator;
        $this->request = $request;
    }
 
    /**
     * Check if the current page is the checkout page.
     *
     * @return bool
     */
    private function isCheckoutPage()
    {
        return $this->request->getModuleName() === 'checkout' && $this->request->getControllerName() === 'index' && $this->request->getActionName() === 'index';
    }
 
    /**
     * Returns the payment configuration array.
     *
     * @return array
     */
    public function getConfig()
    {
        if ($this->isCheckoutPage()) {
            return [
                'payment' => [
                    \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE => [
                        'secure_token' => $this->generator->get(),
                    ],
                ]
            ];
        }
 
        return [];
    }
}