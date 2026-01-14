<?php

namespace CyberSource\SecureAcceptance\Gateway\Command\Flex;

use Exception;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Framework\Message\ManagerInterface as ManagerInterface;
use CyberSource\Core\Model\LoggerInterface;

class GenerateKeyCommand implements CommandInterface
{
    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var ArrayResultFactory
     */
    private $arrayResultFactory;

    /**
     * @var BuilderInterface
     */
    private $requestBuilder;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var HandlerInterface|null
     */
    private $handler;

    private $messageManager;

    private $logger;

    /**
     * @param TransferFactoryInterface $transferFactory
     * @param ArrayResultFactory $arrayResultFactory
     * @param BuilderInterface $requestBuilder
     * @param ValidatorInterface $validator
     * @param ClientInterface $client
     * @param HandlerInterface|null $handler
     */
    public function __construct(
        TransferFactoryInterface $transferFactory,
        ArrayResultFactory $arrayResultFactory,
        BuilderInterface $requestBuilder,
        ValidatorInterface $validator,
        ClientInterface $client,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        ?HandlerInterface $handler = null,
    ) {
        $this->transferFactory = $transferFactory;
        $this->arrayResultFactory = $arrayResultFactory;
        $this->requestBuilder = $requestBuilder;
        $this->validator = $validator;
        $this->client = $client;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->handler = $handler;
    }
    

    /**
     * @param array $commandSubject
     * @return ResultInterface
     * @throws ClientException
     * @throws ConverterException
     * @throws Exception
     */
    public function execute(array $commandSubject)
    {
        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        $response = $this->client->placeRequest($transferO);

        $validationResult = $this->validator->validate(
            array_merge($commandSubject, ['response' => $response])
        );
        try {
            if (!$validationResult->isValid()) {
            // Log the validation failure
            $this->logger->error('Flex Validation failed.');
            }
            } catch (\Exception $e) {
        }


        if (is_array($response) && isset($response['token'])) {
            $response = $response['token'];
            }
            if (isset($response['http_code']) && $response['http_code'] == 401) {
            return ['error' => __('Authentication Failed. Please check your credentials.')];
            } else {
            if ($this->handler !== null) {
            $this->handler->handle($commandSubject, $response);
            }
        }


        return $this->arrayResultFactory->create(['array' => $response]);
    }
}
