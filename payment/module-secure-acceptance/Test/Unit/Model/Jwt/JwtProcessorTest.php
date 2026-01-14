<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Model\Jwt;

use PHPUnit\Framework\TestCase;

class JwtProcessorTest extends TestCase
{
    /**
     * @var \Lcobucci\JWT\Parser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parserMock;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwk\ConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $converterMock;

    /**
     * @var JwtProcessor
     */
    protected $jwtProcessor;

    /**
     * @var \Lcobucci\JWT\Token|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenMock;

    /**
     * @var \Lcobucci\JWT\Signer\Rsa\Sha256Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shaFactoryMock;

    /**
     * @var \Lcobucci\JWT\Signer\Rsa\Sha256|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shaMock;

    protected function setUp()
    {
        $this->parserMock = $this->createMock(\Lcobucci\JWT\Parser::class);
        $this->converterMock = $this->createMock(\CyberSource\SecureAcceptance\Model\Jwk\ConverterInterface::class);
        $this->tokenMock = $this->createMock(\Lcobucci\JWT\Token::class);

        $this->shaFactoryMock = $this->createMock(\Lcobucci\JWT\Signer\Rsa\Sha256Factory::class);
        $this->shaMock = $this->createMock(\Lcobucci\JWT\Signer\Rsa\Sha256::class);
        $this->shaFactoryMock->method('create')->willReturn($this->shaMock);

        $this->jwtProcessor = new JwtProcessor(
            $this->parserMock,
            $this->converterMock,
            $this->shaFactoryMock
        );
    }

    public function testGetFlexPaymentToken()
    {

        $jwt = 'asdasdasdasd';
        $flexToken = 'sometoken';

        $this->parserMock->method('parse')->with($jwt)->willReturn($this->tokenMock);
        $this->tokenMock->method('getClaim')->with('jti')->willReturn($flexToken);

        static::assertEquals($flexToken, $this->jwtProcessor->getFlexPaymentToken($jwt));
    }

    public function testGetCardData()
    {
        $jwt = 'asdasdasdasd';
        $cardData = ['some' => 'data'];

        $this->parserMock->method('parse')->with($jwt)->willReturn($this->tokenMock);
        $this->tokenMock->method('getClaim')->with('data')->willReturn((object)$cardData);

        static::assertEquals($cardData, $this->jwtProcessor->getCardData($jwt));
    }

    public function testGetPublicKey()
    {
        $jwt = 'asdasdasdasd';
        $flx = ['jwk' => 'jwkvalue'];

        $this->parserMock->method('parse')->with($jwt)->willReturn($this->tokenMock);
        $this->tokenMock->method('getClaim')->with('flx')->willReturn((object)$flx);

        $this->converterMock->method('jwkToPem')->willReturnArgument(0);

        static::assertEquals([$flx['jwk']], $this->jwtProcessor->getPublicKey($jwt));
    }

    /**
     * @param $expectedValidationResult
     * @param $validationResult
     *
     * @dataProvider dataProviderTestVerifySignature
     */
    public function testVerifySignature($expectedValidationResult, $validationResult)
    {

        $jwt = '1231231232';
        $key = 'asdasdasd';

        $this->parserMock->method('parse')->with($jwt)->willReturn($this->tokenMock);
        $this->tokenMock
            ->method('verify')
            ->with($this->shaMock, $key)
            ->willReturn($validationResult);

        static::assertEquals($expectedValidationResult, $this->jwtProcessor->verifySignature($jwt, $key));
    }

    public function dataProviderTestVerifySignature()
    {
        return [
            ['expectedValidationResult' => true, 'validationResult' => true],
            ['expectedValidationResult' => false, 'validationResult' => false],
        ];
    }

}
