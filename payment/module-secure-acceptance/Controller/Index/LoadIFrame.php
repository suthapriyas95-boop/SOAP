<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use Magento\Framework\App\Action\Context;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use Magento\Framework\Controller\ResultFactory;
use CyberSource\SecureAcceptance\Helper\Vault;
use Magento\Checkout\Model\Session;

class LoadIFrame extends \Magento\Framework\App\Action\Action
{
    /**
     * @var RequestDataBuilder
     */
    private $helper;

    /**
     * @var Vault
     */
    private $vaultHelper;
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * LoadIFrame constructor.
     * @param Context $context
     * @param RequestDataBuilder $helper
     * @param Vault $vaultHelper
     * @param Session $session
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Context $context,
        RequestDataBuilder $helper,
        Vault $vaultHelper,
        Session $session,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Escaper $escaper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->helper = $helper;
        $this->vaultHelper = $vaultHelper;
        $this->session = $session;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
        $this->escaper = $escaper;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $quote = $this->session->getQuote();

        if ($guestEmail = $this->_request->getParam('quoteEmail')) {
            $quote->setCustomerEmail($guestEmail);
        }

        $this->vaultHelper->setVaultEnabled($this->_request->getParam('vaultIsEnabled'));

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setData(['error' => __('Invalid formkey.')]);
        }

        $quote->reserveOrderId();
        $this->quoteRepository->save($quote);

        $data = [];

        try {
            $paymentData = $this->helper->buildRequestData();

            if (!preg_match('/embedded\/pay/', $paymentData['request_url'])) {
                $paymentData['request_url'] = str_replace('/pay', '/embedded/pay', $paymentData['request_url']);
            }
            $html = '<form id="cybersource-iframe-form" action="'.$this->escaper->escapeHtmlAttr($paymentData['request_url']).'" method="post">';
            foreach ($paymentData as $name => $value) {
                $html .= '<input type="hidden" name="'.$this->escaper->escapeHtmlAttr($name).'" value="'.$this->escaper->escapeHtmlAttr($value).'" />';
            }
            $html .= '</form>';

            $data['html'] = $html;
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        $result->setData($data);
        return $result;
    }
}
