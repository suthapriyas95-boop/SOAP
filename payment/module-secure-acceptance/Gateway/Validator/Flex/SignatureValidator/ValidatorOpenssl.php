<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Gateway\Validator\Flex\SignatureValidator;


class ValidatorOpenssl implements ValidatorInterface
{

    public function validate($dataString, $signature, $publicKey, $algorithm)
    {
        $publicKeyFormatted = "-----BEGIN PUBLIC KEY-----\n" . $publicKey . "\n-----END PUBLIC KEY-----";

        $signature = base64_decode($signature);

        $verificationResult = openssl_verify($dataString, $signature, $publicKeyFormatted, $algorithm);
        return $verificationResult === 1;
    }
}
