<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace CyberSource\SecureAcceptance\Service;

use CyberSource\Core\Service\AbstractConnection;
use Magento\Framework\Exception\LocalizedException;

class CyberSourceSoapApi extends AbstractConnection
{
    /**
     * @param $request
     * @return null
     * @throws \Exception
     */
    public function run($request)
    {
        $log = [
            'request' => (array) $request,
            'client' => static::class
        ];

        $response = null;
        try {
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $response = $this->client->runTransaction($request);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw $e;
        } finally {
            $log['response'] = (array) $response;
            $this->logger->debug($log);
        }

        return $response;
    }
}
