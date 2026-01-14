<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;


class ReceiptPageUrlBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        return [
            'override_custom_receipt_page' => $this->urlBuilder->getUrl(
                'cybersource/index/placeorder',
                ['_secure' => true]
            ),
            'override_custom_cancel_page' => $this->urlBuilder->getUrl(
                'cybersource/index/cancel',
                ['_secure' => true]
            ),
        ];
    }
}
