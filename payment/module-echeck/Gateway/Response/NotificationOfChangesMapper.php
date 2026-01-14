<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Response;


class NotificationOfChangesMapper implements \CyberSource\Core\Gateway\Response\MapperInterface
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

        //TODO: write this

        return $result;
    }

}
