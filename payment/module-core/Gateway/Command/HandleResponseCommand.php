<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Command;

class HandleResponseCommand implements \Magento\Payment\Gateway\CommandInterface
{

    /**
     * @var \Magento\Payment\Gateway\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var \Magento\Payment\Gateway\Response\HandlerInterface
     */
    private $handler;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    private $logger;

    /**
     * @var \CyberSource\Core\Gateway\ErrorMapper\ConfigurableMapper
     */
    private $errorMessageMapper;

    /**
     * @var \Magento\Payment\Gateway\Response\HandlerInterface|null
     */
    private $errorHandler;

    /**
     * HandleResponseCommand constructor.
     *
     * @param \Magento\Payment\Gateway\Response\HandlerInterface $handler
     * @param \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
     * @param \CyberSource\Core\Model\LoggerInterface $logger
     * @param \Magento\Payment\Gateway\Response\HandlerInterface|null $errorHandler
     * @param \CyberSource\Core\Gateway\ErrorMapper\ConfigurableMapper|null $errorMessageMapper
     * @param \Magento\Payment\Gateway\Validator\ValidatorInterface|null $validator
     */
    public function __construct(
        \Magento\Payment\Gateway\Response\HandlerInterface $handler,
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\Core\Model\LoggerInterface $logger,
        ?\Magento\Payment\Gateway\Response\HandlerInterface $errorHandler = null,
        ?\CyberSource\Core\Gateway\ErrorMapper\ConfigurableMapper $errorMessageMapper = null,
        ?\Magento\Payment\Gateway\Validator\ValidatorInterface $validator = null
    ) {
        $this->validator = $validator;
        $this->handler = $handler;
        $this->subjectReader = $subjectReader;
        $this->errorMessageMapper = $errorMessageMapper;
        $this->logger = $logger;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Handles Token creation Response
     *
     * @param array $commandSubject
     *
     * @return void
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        $response = $this->subjectReader->readResponse($commandSubject);

        $this->logger->debug(
            [
                'client' => static::class,
                'response' => $response,
            ]
        );

        if ($this->validator !== null) {
            $validationResult = $this->validator->validate($commandSubject);

            if (!$validationResult->isValid()) {

                $messages = [];
                foreach ($validationResult->getFailsDescription() as $error) {
                    $messages[] = $error->getText();
                    $this->logger->critical($error);
                }

                $exceptionMessage = !empty($messages)
                    ? __(implode(PHP_EOL, $this->getErrorsDescription($messages)))
                    : __('Error while handling response');

                if ($this->errorHandler !== null) {
                    $commandSubject['fails_description'] = $validationResult->getFailsDescription();
                    $this->errorHandler->handle($commandSubject, $response);
                }

                throw new \Magento\Payment\Gateway\Command\CommandException($exceptionMessage);
            }
        }

        $this->handler->handle($commandSubject, $response);
    }

    public function getErrorsDescription($errors)
    {
        if ($this->errorMessageMapper === null) {
            return $errors;
        }

        $result = [];

        foreach ($errors as $error) {
            $result[] = $this->errorMessageMapper->getMessage($error);
        }

        return $result;
    }
}
