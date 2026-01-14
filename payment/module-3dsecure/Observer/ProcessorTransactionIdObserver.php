<?php

namespace CyberSource\ThreeDSecure\Observer;

class ProcessorTransactionIdObserver implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    ) {
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();

        $exception = $event->getData('exception');

        if (!$exception instanceof \CyberSource\ThreeDSecure\Gateway\PaEnrolledException) {
            return;
        }

        $exceptionData = $exception->getDetails();

        $processorTransactionId = $exceptionData['order']['OrderDetails']['TransactionId'] ?? null;

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $event->getData('quote');

        $quote->getPayment()->setAdditionalInformation(
            \CyberSource\ThreeDSecure\Gateway\Validator\PaEnrolledValidator::KEY_PAYER_AUTH_ENROLL_TRANSACTION_ID,
            $processorTransactionId
        );

        $this->cartRepository->save($quote);
    }
}
