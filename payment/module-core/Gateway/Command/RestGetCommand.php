<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Command;


class RestGetCommand implements \Magento\Payment\Gateway\CommandInterface
{

    /**
     * @var \Magento\Payment\Gateway\Http\TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var \Magento\Payment\Gateway\Command\Result\ArrayResultFactory
     */
    private $arrayResultFactory;

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface
     */
    private $builder;

    /**
     * @var \Magento\Payment\Gateway\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var \Magento\Payment\Gateway\Http\ClientInterface
     */
    private $client;

    /**
     * @var \CyberSource\Core\Gateway\Response\MapperInterface
     */
    private $mapper;

    /**
     * @var string
     */
    private $exceptionMessage;


    public function __construct(
        \Magento\Payment\Gateway\Http\TransferFactoryInterface $transferFactory,
        \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $arrayResultFactory,
        \Magento\Payment\Gateway\Request\BuilderInterface $builder,
        \Magento\Payment\Gateway\Validator\ValidatorInterface $validator,
        \Magento\Payment\Gateway\Http\ClientInterface $client,
        $exceptionMessage = 'Unable to load report data',
        ?\CyberSource\Core\Gateway\Response\MapperInterface $mapper = null
    ) {
        $this->transferFactory = $transferFactory;
        $this->arrayResultFactory = $arrayResultFactory;
        $this->builder = $builder;
        $this->validator = $validator;
        $this->client = $client;
        $this->mapper = $mapper;
        $this->exceptionMessage = $exceptionMessage;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject)
    {
        $transferO = $this->transferFactory->create(
            $this->builder->build($commandSubject)
        );

        if ($storeId = $commandSubject['store_id'] ?? null) {
            $this->client->setStoreId($storeId);
        }

        $result = [];

        try {
            $clientResponse = $this->client->placeRequest($transferO);
            $validationResult = $this->validator->validate(
                array_merge($commandSubject, ['response' => $clientResponse])
            );

            if (!$validationResult->isValid()) {
                throw new \Magento\Payment\Gateway\Command\CommandException(__('Result invalid.'));
            }

            $result = is_null($this->mapper)
                ? $clientResponse
                : $this->mapper->map($commandSubject, $clientResponse);

        } catch (\CyberSource\Core\Gateway\Validator\NotFoundException $e) {
            // if no data found we return empty array
            return $this->arrayResultFactory->create(['array' => []]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            throw new \Magento\Payment\Gateway\Command\CommandException(
                __($this->exceptionMessage),
                $e
            );
        }

        return $this->arrayResultFactory->create(['array' => $result]);
    }
}
