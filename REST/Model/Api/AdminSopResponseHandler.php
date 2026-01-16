<?php
namespace CyberSource\Payment\Model\Api\Admin;

use CyberSource\Payment\Api\Admin\SopResponseHandlerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\AdminOrder\Model\Create;
use CyberSource\Payment\Gateway\Config\Config;
use CyberSource\Payment\Helper\ResponseValidator;

/**
 * Handle SOP responses for admin orders
 */
class AdminSopResponseHandler implements SopResponseHandlerInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var Create
     */
    private $orderCreate;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ResponseValidator
     */
    private $responseValidator;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderManagementInterface $orderManagement
     * @param Create $orderCreate
     * @param Config $config
     * @param ResponseValidator $responseValidator
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        OrderManagementInterface $orderManagement,
        Create $orderCreate,
        Config $config,
        ResponseValidator $responseValidator
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderManagement = $orderManagement;
        $this->orderCreate = $orderCreate;
        $this->config = $config;
        $this->responseValidator = $responseValidator;
    }

    /**
     * @inheritdoc
     */
    public function handleResponse(array $response, array $orderData)
    {
        try {
            // Validate signature
            if (!$this->validateSignature($response)) {
                throw new LocalizedException(__('Invalid payment response signature.'));
            }

            // Process response and create order
            $orderResult = $this->processResponse($response);

            // Create order using AdminOrder\Create
            $this->orderCreate->setIsValidate(true);
            $this->orderCreate->importPostData($orderData);
            $order = $this->orderCreate->createOrder();

            return [
                'success' => true,
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'redirect_url' => '/admin/sales/order/view/order_id/' . $order->getId()
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to process payment response: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateSignature(array $response)
    {
        return $this->responseValidator->validateSignature($response);
    }

    /**
     * @inheritdoc
     */
    public function processResponse(array $response)
    {
        // Extract relevant data from CyberSource response
        return [
            'transaction_id' => $response['transaction_id'] ?? '',
            'auth_code' => $response['auth_code'] ?? '',
            'decision' => $response['decision'] ?? '',
            'avs_result' => $response['avs_result'] ?? '',
            'cvv_result' => $response['cvv_result'] ?? '',
            'amount' => $response['amount'] ?? 0
        ];
    }
}