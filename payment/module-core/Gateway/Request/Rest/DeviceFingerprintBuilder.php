<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\Gateway\Request\Rest;


class DeviceFingerprintBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var bool
     */
    private $isAdmin;

    /**
     * DeviceFingerprintBuilder constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param bool $isAdmin
     */
    public function __construct(\Magento\Checkout\Model\Session $checkoutSession, $isAdmin = false)
    {
        $this->checkoutSession = $checkoutSession;
        $this->isAdmin = $isAdmin;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        if ($this->isAdmin || !$fingerPrintId = $this->checkoutSession->getFingerprintId()) {
            return [];
        }

        return [
            'deviceInformation' => [
                'fingerprintSessionId' => $fingerPrintId,
                'useRawFingerprintSessionId' => true
            ]
        ];

    }
}
