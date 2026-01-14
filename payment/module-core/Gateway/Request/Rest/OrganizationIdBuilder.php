<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;


class OrganizationIdBuilder  implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \CyberSource\Core\Model\Config
     */
    private $config;

    public function __construct(
        \CyberSource\Core\Model\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $storeId = $buildSubject['store_id'] ?? null;

        return [
            'organizationId' => $this->config->getMerchantId($storeId),
        ];
    }
}
