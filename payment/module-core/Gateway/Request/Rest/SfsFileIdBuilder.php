<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;


class SfsFileIdBuilder  implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $fileId = $buildSubject['fileId'] ?? null;

        if (!$fileId) {
            throw new \InvalidArgumentException('File Id must be provided.');
        }

        return [\CyberSource\Core\Gateway\Http\Client\Rest::KEY_URL_PARAMS => [$fileId]];
    }
}
