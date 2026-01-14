<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CyberSource\SecureAcceptance\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        TransferBuilder $transferBuilder
    ) {
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $requestMethod = null;
        $requestUri = null;

        if (array_key_exists('method', $request)) {
            $requestMethod = ($request['method']) ?: 'POST';
            $requestUri = isset($request['uri']) ? $request['uri'] : null;

            unset($request['uri']);
            unset($request['method']);

            $request = json_encode($request);
        }

        return $this->transferBuilder
            ->setUri($requestUri)
            ->setBody($request)
            ->setMethod($requestMethod)
            ->build();
    }
}
