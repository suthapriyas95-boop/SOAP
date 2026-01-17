<?php
namespace CyberSource\Payment\Model\Api\Admin;

use CyberSource\Payment\Api\Admin\PaymentProcessorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use CyberSource\Payment\Gateway\Request\Rest\AuthorizeRequest;
use CyberSource\Payment\Gateway\Request\Rest\SaleRequest;
use CyberSource\Payment\Gateway\Request\Rest\CaptureRequest;
use CyberSource\Payment\Gateway\Request\Rest\VoidRequest;
use CyberSource\Payment\Gateway\Request\Rest\RefundRequest;
use CyberSource\Payment\Gateway\Command\GatewayCommand;

/**
 * Process CyberSource payments for admin orders
 */
class AdminPaymentProcessor implements PaymentProcessorInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GatewayCommand
     */
    private $gatewayCommand;

    /**
     * @var AuthorizeRequest
     */
    private $authorizeRequest;

    /**
     * @var SaleRequest
     */
    private $saleRequest;

    /**
     * @var CaptureRequest
     */
    private $captureRequest;

    /**
     * @var VoidRequest
     */
    private $voidRequest;

    /**
     * @var RefundRequest
     */
    private $refundRequest;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param GatewayCommand $gatewayCommand
     * @param AuthorizeRequest $authorizeRequest
     * @param SaleRequest $saleRequest
     * @param CaptureRequest $captureRequest
     * @param VoidRequest $voidRequest
     * @param RefundRequest $refundRequest
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        GatewayCommand $gatewayCommand,
        AuthorizeRequest $authorizeRequest,
        SaleRequest $saleRequest,
        CaptureRequest $captureRequest,
        VoidRequest $voidRequest,
        RefundRequest $refundRequest
    ) {
        $this->orderRepository = $orderRepository;
        $this->gatewayCommand = $gatewayCommand;
        $this->authorizeRequest = $authorizeRequest;
        $this->saleRequest = $saleRequest;
        $this->captureRequest = $captureRequest;
        $this->voidRequest = $voidRequest;
        $this->refundRequest = $refundRequest;
    }

    /**
     * @inheritdoc
     */
    public function authorize(array $paymentData)
    {
        try {
            $requestData = $this->authorizeRequest->build($paymentData);
            $result = $this->gatewayCommand->execute($requestData);

            return [
                'success' => true,
                'transaction_id' => $result['transaction_id'] ?? '',
                'auth_code' => $result['auth_code'] ?? '',
                'request_id' => $result['request_id'] ?? '',
                'amount' => $result['amount'] ?? 0,
                'decision' => $result['decision'] ?? '',
                'avs_result' => $result['avs_result'] ?? '',
                'cvv_result' => $result['cvv_result'] ?? ''
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Authorization failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function sale(array $paymentData)
    {
        try {
            $requestData = $this->saleRequest->build($paymentData);
            $result = $this->gatewayCommand->execute($requestData);

            return [
                'success' => true,
                'transaction_id' => $result['transaction_id'] ?? '',
                'auth_code' => $result['auth_code'] ?? '',
                'request_id' => $result['request_id'] ?? '',
                'capture_request_id' => $result['capture_request_id'] ?? '',
                'amount' => $result['amount'] ?? 0,
                'decision' => $result['decision'] ?? '',
                'avs_result' => $result['avs_result'] ?? '',
                'cvv_result' => $result['cvv_result'] ?? '',
                'capture_status' => $result['capture_status'] ?? ''
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Sale failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function capture(array $paymentData)
    {
        try {
            $requestData = $this->captureRequest->build($paymentData);
            $result = $this->gatewayCommand->execute($requestData);

            return [
                'success' => true,
                'capture_request_id' => $result['capture_request_id'] ?? '',
                'amount' => $result['amount'] ?? 0,
                'decision' => $result['decision'] ?? ''
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Capture failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function void(array $paymentData)
    {
        try {
            $requestData = $this->voidRequest->build($paymentData);
            $result = $this->gatewayCommand->execute($requestData);

            return [
                'success' => true,
                'void_request_id' => $result['void_request_id'] ?? '',
                'decision' => $result['decision'] ?? ''
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Void failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function refund(array $paymentData)
    {
        try {
            $requestData = $this->refundRequest->build($paymentData);
            $result = $this->gatewayCommand->execute($requestData);

            return [
                'success' => true,
                'refund_request_id' => $result['refund_request_id'] ?? '',
                'amount' => $result['amount'] ?? 0,
                'decision' => $result['decision'] ?? ''
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Refund failed: %1', $e->getMessage()));
        }
    }
}