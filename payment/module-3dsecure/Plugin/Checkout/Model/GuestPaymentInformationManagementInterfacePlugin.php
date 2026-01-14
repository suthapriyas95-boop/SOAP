<?php

namespace CyberSource\ThreeDSecure\Plugin\Checkout\Model;

class GuestPaymentInformationManagementInterfacePlugin
{

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var \Magento\Quote\Model\GuestCart\GuestCartRepository
     */
    private $guestCartRepository;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\GuestCart\GuestCartRepository $guestCartRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->guestCartRepository = $guestCartRepository;
    }

    public function aroundSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Api\GuestPaymentInformationManagementInterface $subject,
        callable $proceed,
        $cartId,
        $email,
        ?\Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        ?\Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        try {
            return $proceed($cartId, $email, $paymentMethod, $billingAddress);
        } catch (\Magento\Framework\Exception\CouldNotSaveException $e) {

            if (!$e->getPrevious() instanceof \CyberSource\ThreeDSecure\Gateway\PaEnrolledException) {
                throw $e;
            }

            $quote = $this->guestCartRepository->get($cartId);

            $exceptionData = $e->getPrevious()->getDetails();
            $processorTransactionId = $exceptionData['order']['OrderDetails']['TransactionId'] ?? null;

            $quote->getPayment()->setAdditionalInformation(
                \CyberSource\ThreeDSecure\Gateway\Validator\PaEnrolledValidator::KEY_PAYER_AUTH_ENROLL_TRANSACTION_ID,
                $processorTransactionId
            );

            $this->cartRepository->save($quote);

            throw $e;
        }

    }

}
