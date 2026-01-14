<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Response;


class ConversionDetailsMapper implements \CyberSource\Core\Gateway\Response\MapperInterface
{

    const KEY_CONVERSION_DETAILS  = 'conversionDetails';
    const KEY_MRN = 'merchantReferenceNumber';
    const KEY_NEW_DECISION = 'newDecision';
    const KEY_DECISION = 'originalDecision';
    const KEY_REQUEST_ID = 'requestId';

    /**
     * @inheritDoc
     */
    public function map(array $handlingSubject, array $response)
    {

        $result = [];

        foreach ($response[static::KEY_CONVERSION_DETAILS] as $conversion) {
            $result[] = [
                'order_increment_id' => (string)$conversion[static::KEY_MRN],
                'decision' => (string)$conversion[static::KEY_DECISION],
                'new_decision' => (string)$conversion[static::KEY_NEW_DECISION],
                'transaction_id' => (string)$conversion[static::KEY_REQUEST_ID],
            ];
        }

        return $result;
    }

}
