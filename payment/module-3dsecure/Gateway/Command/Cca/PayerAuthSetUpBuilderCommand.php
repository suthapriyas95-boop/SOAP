<?php

namespace CyberSource\ThreeDSecure\Gateway\Command\Cca;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader;

class PayerAuthSetUpBuilderCommand implements CommandInterface
{
    const KEY_PAYER_AUTH_ENROLL_REFERENCE_ID = 'payer_auth_enroll_reference_id';

    /**
     * @var CommandInterface
     */
    private $subscriptionRetrieveCommand;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

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
     * @var ClientInterface
     */
    private $client;

    /**
     * @param CommandInterface $subscriptionRetrieveCommand
     * @param SubjectReader $subjectReader
     * @param TransferFactoryInterface $transferFactory
     * @param ArrayResultFactory $arrayResultFactory
     * @param BuilderInterface $requestBuilder
     * @param ClientInterface $client
     */
    public function __construct(
        CommandInterface $subscriptionRetrieveCommand,
        SubjectReader $subjectReader,
        TransferFactoryInterface $transferFactory,
        ArrayResultFactory $arrayResultFactory,
        BuilderInterface $requestBuilder,
        ClientInterface $client
    ) {
        $this->subscriptionRetrieveCommand = $subscriptionRetrieveCommand;
        $this->subjectReader = $subjectReader;
        $this->transferFactory = $transferFactory;
        $this->arrayResultFactory = $arrayResultFactory;
        $this->requestBuilder = $requestBuilder;
        $this->client = $client;
        
    }

    /**
     * @param array $commandSubject
     * @param array $buildSubject
     * @return ResultInterface
     * @throws ClientException
     * @throws ConverterException
     * @throws Exception
     */
    public function execute(array $commandSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($commandSubject);
        $payment = $paymentDO->getPayment();

        try {
            $result = $this->subscriptionRetrieveCommand->execute($commandSubject)->get();

            $cardNumber = $result['cardAccountNumber'] ?? null;

            if ($cardType = $result['cardType'] ?? null) {
                $payment->setAdditionalInformation('cardType', $cardType);
            }

            if ($cardNumber) {
                $commandSubject['cardBin'] = substr($cardNumber, 0, 6);
            }

        } catch (\Exception $e) {

        }

        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        $response = $this->client->placeRequest($transferO);
        $response = json_decode(json_encode($response['payerAuthSetupReply']), true);

        $accessToken = '';
        $deviceDataCollection = '';
        $referenceID ='';

        if (isset($response['accessToken'])) {
            $accessToken = $response['accessToken'];
        }

        if (isset($response['deviceDataCollectionURL'])) {
            $deviceDataCollection = $response['deviceDataCollectionURL'];
        }

        if (isset($response['referenceID'])) {
            $referenceID = $response['referenceID'];
            $payment->setAdditionalInformation(PayerAuthSetUpBuilderCommand::KEY_PAYER_AUTH_ENROLL_REFERENCE_ID, $referenceID);
        }

        return $this->createArrayResult(['accessToken' => $accessToken, 'deviceDataCollectionURL' => $deviceDataCollection, 'referenceID' => $referenceID]);
    }

    /**
     * @param array $data
     * @return \Magento\Payment\Gateway\Command\Result\ArrayResult
     */
    private function createArrayResult(array $data)
    {
        return $this->arrayResultFactory->create(['array' => $data]);
    }
}
