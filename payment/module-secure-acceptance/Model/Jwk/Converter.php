<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Model\Jwk;

class Converter implements ConverterInterface
{
	
	/**
    * @var \Magento\Framework\App\ProductMetadataInterface
    */
	private $productMetadata;
	
	/**
    * @var \CyberSource\Core\Model\LoggerInterface
    */
    protected $logger;
	

    public function __construct(
		\Magento\Framework\App\ProductMetadataInterface $productMetadata,
		\CyberSource\Core\Model\LoggerInterface $logger
		
    ) {
		$this->productMetadata = $productMetadata;
		$this->logger = $logger;
    }


	/*
	* @param array $jwkArray
	* Generate public key using BigInteger & RSA class
	* @return string
	*/
    public function jwkToPem($jwkArray)
    {
		// magento latest version 2.4.4 phpseclib version 3 in use
		if($this->productMetadata->getVersion() >= '2.4.4'){

			$exponent = new \phpseclib3\Math\BigInteger(base64_decode($jwkArray['e']), 256);
			$modulus  = new \phpseclib3\Math\BigInteger(base64_decode(strtr($jwkArray['n'] ?? '', '-_', '+/'), true), 256);
			$publicKey = \phpseclib3\Crypt\PublicKeyLoader::loadPublicKey(['e' => $exponent, 'n' => $modulus]);
			
			return $publicKey->toString('PKCS8');
			
		}else{
			// magento  old versions < 2.4.4 use phpseclib version 2 library
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			
			$rsa = $objectManager->create(\phpseclib\Crypt\RSA::class);


			$exponent = $objectManager->create(\phpseclib\Math\BigInteger::class,[
				'x' => base64_decode($jwkArray['e']),
				'base' => 256,
			]);


			$modulus = $objectManager->create(\phpseclib\Math\BigInteger::class,[
				'x' => base64_decode(strtr($jwkArray['n'] ?? '', '-_', '+/'), true),
				'base' => 256,
			]);

			$rsa->loadKey(['e' => $exponent, 'n' => $modulus]);

			return $rsa->getPublicKey(\phpseclib\Crypt\RSA::PUBLIC_FORMAT_PKCS8);

		}
    }
}
