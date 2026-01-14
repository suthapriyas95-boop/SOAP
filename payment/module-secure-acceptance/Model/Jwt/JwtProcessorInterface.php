<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Model\Jwt;


interface JwtProcessorInterface
{

    public function getFlexPaymentToken($jwtString);

    public function getCardData($jwtString);

    public function verifySignature($jwtString, $key);

    public function getPublicKey($jwtString);

}
