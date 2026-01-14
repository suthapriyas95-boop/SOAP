<?php

namespace CyberSource\SecureAcceptance\Model\Jwt;

use CyberSource\Core\Model\LoggerInterface;

class JwtProcessor implements JwtProcessorInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtHelper
     */
    private $jwtHelper;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwk\ConverterInterface
     */
    private $jwkConverter;

    /**
     * @var \Lcobucci\JWT\Signer\Rsa\Sha256Factory
     */
    private $sha256Factory;

    /**
     * @var \Lcobucci\JWT\Validation\Validator
     */
    private $validator;

    /**
     * @var \Lcobucci\JWT\Validation\Constraint\SignedWithFactory
     */
    private $signedWithConstraintFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \CyberSource\SecureAcceptance\Model\Jwt\JwtHelper $jwtHelper,
        \CyberSource\SecureAcceptance\Model\Jwk\ConverterInterface $jwkConverter,
        \Lcobucci\JWT\Signer\Rsa\Sha256Factory $sha256Factory,
        \Lcobucci\JWT\Validation\Validator $validator,
        \Lcobucci\JWT\Validation\Constraint\SignedWithFactory $signedWithConstraintFactory,
        LoggerInterface $logger
    ) {
        $this->jwtHelper = $jwtHelper;
        $this->jwkConverter = $jwkConverter;
        $this->sha256Factory = $sha256Factory;
        $this->validator = $validator;
        $this->signedWithConstraintFactory = $signedWithConstraintFactory;
        $this->logger = $logger;
    }

    private function parse($jwtString)
    {
        return $this->jwtHelper->parse($jwtString);
    }

    public function getFlexPaymentToken($jwtString)
    {
        $token = $this->parse($jwtString);
        return $this->getClaim($token, 'jti');
    }

    public function getCardData($jwtString)
    {
        $token = $this->parse($jwtString);
        return (array) $this->getClaim($token, 'data');
    }

    public function getPublicKey($jwtString)
    {
        $token = $this->parse($jwtString);
        $flx = $this->getClaim($token, 'flx');
        $jwk = $this->jwtHelper->getJwk($flx);
        $pemKey = $this->jwkConverter->jwkToPem($jwk);
        return $pemKey;
    }

    public function verifySignature($jwtString, $key)
    {
        $token = $this->jwtHelper->parse($jwtString);

        return $this->validator->validate(
            $token,
            $this->signedWithConstraintFactory->create(
                [
                    'signer' => $this->sha256Factory->create(),
                    'key' => $this->jwtHelper->getJwtKeyObj($key)
                ]
            )
        );
    }

    /**
     * @param \Lcobucci\JWT\Token $token
     * @param $name
     */
    private function getClaim($token, $name)
    {
        /** @var \Lcobucci\JWT\Token\DataSet $claims */
        $claims = $token->claims();

        return $claims->get($name);
    }
}
