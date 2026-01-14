<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Response\Flex;


class PublicKeyHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface
     */
    private $jwtProcessor;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface $jwtProcessor
    ) {
        $this->subjectReader = $subjectReader;
        $this->jwtProcessor = $jwtProcessor;
    }

    /**
     * @inheritDoc
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDo = $this->subjectReader->readPayment($handlingSubject);

        $jwtString = $response['response'] ?? null;

        if (!$jwtString) {
            throw new \Magento\Payment\Gateway\Command\CommandException(__('Cannot get JWT from the gateway.'));
        }

        $publicKey = $this->jwtProcessor->getPublicKey($jwtString);

        
        $paymentDo->getPayment()->setAdditionalInformation('captureContext', $jwtString);

        $paymentDo->getPayment()->setAdditionalInformation('microformPublicKey', $publicKey);

    }
}
