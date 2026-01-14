<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Command;


class GetNotificationOfChangesReportCommand implements \Magento\Payment\Gateway\CommandInterface
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
     * @var \CyberSource\Core\Gateway\Response\MapperInterface
     */
    private $mapper;

    /**
     * @var \Magento\Payment\Gateway\Http\ClientInterface
     */
    private $client;

    public function __construct(
        \Magento\Payment\Gateway\Http\TransferFactoryInterface $transferFactory,
        \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $arrayResultFactory,
        \Magento\Payment\Gateway\Request\BuilderInterface $builder,
        \Magento\Payment\Gateway\Validator\ValidatorInterface $validator,
        \CyberSource\Core\Gateway\Response\MapperInterface $mapper,
        \Magento\Payment\Gateway\Http\ClientInterface $client
    ) {
        $this->transferFactory = $transferFactory;
        $this->arrayResultFactory = $arrayResultFactory;
        $this->builder = $builder;
        $this->validator = $validator;
        $this->mapper = $mapper;
        $this->client = $client;
    }


    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject)
    {
        $transferO = $this->transferFactory->create(
            $this->builder->build($commandSubject)
        );

        $result = [];

        try {
            $clientResponse = $this->client->placeRequest($transferO);
            $validationResult = $this->validator->validate(
                array_merge($commandSubject, ['response' => $clientResponse])
            );

            if (!$validationResult->isValid()) {
                throw new \Magento\Payment\Gateway\Command\CommandException(__('Result invalid.'));
            }

            $result = $this->mapper->map($commandSubject, $clientResponse);

        } catch (\CyberSource\Core\Gateway\Validator\NotFoundException $e) {
            // if no conversion found we return empty array
            return $this->arrayResultFactory->create(['array' => []]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            throw new \Magento\Payment\Gateway\Command\CommandException(
                __('Failed to load notification of changes report. Please verify extension configuration.'),
                $e
            );
        }

        return $this->arrayResultFactory->create(['array' => $result]);
    }
}
