<?php
namespace CyberSource\Payment\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;

/**
 * REST Gateway Command for CyberSource
 */
class RestGatewayCommand implements CommandInterface
{
    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param HandlerInterface $handler
     * @param ValidatorInterface $validator
     */
    public function __construct(
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null
    ) {
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject)
    {
        $transfer = $this->transferFactory->create($commandSubject);

        $response = $this->client->placeRequest($transfer);

        if ($this->validator) {
            $this->validator->validate($commandSubject, $response);
        }

        if ($this->handler) {
            $this->handler->handle($commandSubject, $response);
        }

        return $response;
    }
}