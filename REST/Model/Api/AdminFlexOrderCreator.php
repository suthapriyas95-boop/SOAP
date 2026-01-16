<?php
namespace CyberSource\Payment\Model\Api\Admin;

use CyberSource\Payment\Api\Admin\FlexOrderCreatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use CyberSource\Payment\Model\Api\AdminTokenGenerator;
use CyberSource\Payment\Api\Admin\Vault\TokenManagerInterface;

/**
 * Create orders using Flex microform tokens for admin
 */
class AdminFlexOrderCreator implements FlexOrderCreatorInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var AdminTokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var TokenManagerInterface
     */
    private $tokenManager;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CartManagementInterface $quoteManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param AdminTokenGenerator $tokenGenerator
     * @param TokenManagerInterface $tokenManager
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CartManagementInterface $quoteManagement,
        OrderRepositoryInterface $orderRepository,
        AdminTokenGenerator $tokenGenerator,
        TokenManagerInterface $tokenManager
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->tokenGenerator = $tokenGenerator;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @inheritdoc
     */
    public function createOrder(
        $quoteId,
        $token,
        array $cardData,
        array $orderData
    ) {
        try {
            // Validate token and card data
            if (!$this->validateTokenData($token, $cardData)) {
                throw new LocalizedException(__('Invalid token or card data.'));
            }

            $quote = $this->quoteRepository->get($quoteId);

            // Set payment data on quote
            $payment = $quote->getPayment();
            $payment->setMethod('cybersource');
            $payment->setAdditionalInformation('flexJwt', $token);
            $payment->setAdditionalInformation('cc_type', $cardData['cc_type'] ?? '');
            $payment->setAdditionalInformation('cc_exp_month', $cardData['cc_exp_month'] ?? '');
            $payment->setAdditionalInformation('cc_exp_year', $cardData['cc_exp_year'] ?? '');

            // Process payment
            $paymentResult = $this->processPayment($quoteId, $token);

            // Create order
            $order = $this->quoteManagement->submit($quote);

            // Save token if requested
            if (isset($cardData['save_card']) && $cardData['save_card']) {
                $this->tokenManager->saveToken($cardData, $paymentResult);
            }

            return [
                'success' => true,
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'transaction_id' => $paymentResult['transaction_id'] ?? '',
                'redirect_url' => '/admin/sales/order/view/order_id/' . $order->getId()
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to create order: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateTokenData($token, array $cardData)
    {
        // Validate JWT token
        if (!$this->tokenGenerator->validateToken($token)) {
            return false;
        }

        // Validate required card data
        $required = ['cc_type', 'cc_exp_month', 'cc_exp_year'];
        foreach ($required as $field) {
            if (!isset($cardData[$field]) || empty($cardData[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function processPayment($quoteId, $token)
    {
        // This would integrate with CyberSource REST API
        // For now, return mock successful payment result
        return [
            'transaction_id' => '5123456789012345678901',
            'auth_code' => 'ABC123',
            'decision' => 'ACCEPT',
            'avs_result' => 'Y',
            'cvv_result' => 'M',
            'amount' => 100.00
        ];
    }
}