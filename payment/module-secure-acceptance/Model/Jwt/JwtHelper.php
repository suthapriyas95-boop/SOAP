<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Model\Jwt;

class JwtHelper
{

    /**
     *
     * @var \Lcobucci\JWT\Parser|\Lcobucci\JWT\Token\Parser
     */
    private $parser;

    /**
     * Boolean to check if Parser is an interface
     *
     * @var boolean
     */
    private $isParserInterface = false;
    /**
     * Logger
     *
     * @var \CyberSource\Core\Model\Logger
     */
    private $logger;

    private $encoderDecoder;

    protected $_objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \CyberSource\Core\Model\Logger $logger
    )
    {
        $this->_objectManager = $objectManager;
        $this->logger = $logger;
        if(interface_exists('\Lcobucci\JWT\Parser')) {
            $this->isParserInterface = true;
            $this->encoderDecoder = $this->_objectManager->create(\Lcobucci\JWT\Encoding\JoseEncoder::class);
            $this->parser = $this->_objectManager->create(\Lcobucci\JWT\Token\Parser::class, ['decoder' => $this->encoderDecoder]);
        }
        else 
        {
            $this->parser = $this->_objectManager->create(\Lcobucci\JWT\Parser::class);
        }
    }

    public function parse($jwtString){
        return $this->parser->parse($jwtString);
    }

    public function getTokenBuilder(){
        if($this->isParserInterface){
            $chainFormatter = \Lcobucci\JWT\Encoding\ChainedFormatter::default();
            // return new \Lcobucci\JWT\Token\Builder($this->encoderDecoder, $chainFormatter);
            return $this->_objectManager->create(\Lcobucci\JWT\Token\Builder::class, ['encoder' => $this->encoderDecoder, 'claimFormatter' => $chainFormatter]);
        }
        else{
            return $this->_objectManager->create(\Lcobucci\JWT\Builder::class);
        }
        
    }

    public function getJwk($claims){
        $jwk = [];
        if($this->isParserInterface) {
            $jwk = $claims['jwk'];
        }
        else {
            $jwk = (array)$claims->jwk;
        }    
        return $jwk;
    }

    public function getJwtKeyObj($key) {
        if($this->isParserInterface)
            return \Lcobucci\JWT\Signer\Key\InMemory::plainText($key);
        else
            return $this->_objectManager->create(\Lcobucci\JWT\Signer\Key::class, ['content' => $key]);
        
    }
}
