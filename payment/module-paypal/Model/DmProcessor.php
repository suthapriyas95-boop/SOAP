<?php

namespace CyberSource\PayPal\Model;

class DmProcessor implements \CyberSource\Core\DM\TransactionProcessorInterface
{

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface
     */
    private $saleRequestBuilder;

    /**
     * @var \CyberSource\PayPal\Service\CyberSourcePayPalSoapAPI
     */
    private $payPalSoapAPI;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * DmProcessor constructor.
     *
     * @param \Magento\Payment\Gateway\Request\BuilderInterface $saleRequestBuilder
     * @param \CyberSource\PayPal\Service\CyberSourcePayPalSoapAPI $payPalSoapAPI
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \CyberSource\Core\Model\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Payment\Gateway\Request\BuilderInterface $saleRequestBuilder,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactory $paymentDataObjectFactory,
        \CyberSource\PayPal\Service\CyberSourcePayPalSoapAPI $payPalSoapAPI,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \CyberSource\Core\Model\LoggerInterface $logger
    ) {
        $this->saleRequestBuilder = $saleRequestBuilder;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->payPalSoapAPI = $payPalSoapAPI;
        $this->quoteRepository = $quoteRepository;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @throws \Exception
     */
    public function settle($payment)
    {
        $orderSetupTransactionId = $payment->getAdditionalInformation(\CyberSource\PayPal\Model\Express\Checkout::PAYMENT_INFO_TRANSPORT_ORDER_SETUP_TXN_ID);

        if (!$orderSetupTransactionId) {
            return;
        }

        try {
            $quote = $this->quoteRepository->get($payment->getOrder()->getQuoteId());
            $quote->setStoreId($payment->getOrder()->getStoreId()); //set correct store_id for the quote

            if ($this->hasCaptureTransaction($payment)) {
                $this->runSale($payment, $quote);
            }

        } catch (\Exception $e) {
            $this->logger->error('DM: Error while settling paypal transaction:' . $e->getMessage());
        }
    }

    private function isValidResponse($response)
    {
        return $response
            && isset($response['decision'])
            && $response['decision'] == \CyberSource\Core\Cron\DecisionManagerReport::DM_ACCEPT
            && isset($response['requestID']);
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param $quote
     * @param $orderSetupTransactionId
     * @return DmProcessor
     * @throws \Exception
     */
    private function runSale($payment, $quote)
    {

        $saleRequest = $this->saleRequestBuilder->build(
            [
                'payment' => $this->paymentDataObjectFactory->create($payment)
            ]
        );

        $saleRequest['decisionManager']['enabled'] = 'false'; // disable DM processing to settle this transaction properly

        $result = (array)$this->payPalSoapAPI->saleService((object)$saleRequest);

        if (!$this->isValidResponse($result)) {
            return $this;
        }

        $payment->setTransactionId($result['requestID']);
        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     */
    public function cancel($payment)
    {

        if ($this->hasCaptureTransaction($payment)) {
            return; // this is Sale action, so there is nothing to reverse here
        }

        try {
            $payment->getMethodInstance()->cancel($payment);
        } catch (\Exception $e) {
            $this->logger->error('DM: Error while cancelling PayPal transaction:' . $e->getMessage());
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return bool
     */
    private function hasCaptureTransaction($payment)
    {
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('payment_id')
                    ->setValue($payment->getId())
                    ->create(),
            ]
        );

        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('txn_type')
                    ->setValue(\Magento\Sales\Api\Data\TransactionInterface::TYPE_CAPTURE)
                    ->create(),
            ]
        );

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $count = $this->transactionRepository->getList($searchCriteria)->getTotalCount();
        return (boolean) $count;
    }
}
