<?php
namespace CyberSource\Payment\Controller\Rest\Admin\Payment;

use CyberSource\Payment\Api\Admin\PaymentProcessorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;

/**
 * Process sale (authorize + capture) for admin orders
 */
class Sale extends \Magento\Framework\Webapi\Rest\Controller
{
    /**
     * @var PaymentProcessorInterface
     */
    private $paymentProcessor;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param PaymentProcessorInterface $paymentProcessor
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        PaymentProcessorInterface $paymentProcessor,
        Request $request,
        Response $response
    ) {
        $this->paymentProcessor = $paymentProcessor;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Process sale
     *
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    public function execute()
    {
        try {
            $paymentData = $this->request->getBodyParams();

            // Validate required fields
            $this->validatePaymentData($paymentData);

            $result = $this->paymentProcessor->sale($paymentData);

            return $this->response->setHttpResponseCode(200)->setBody(json_encode($result));
        } catch (LocalizedException $e) {
            return $this->response->setHttpResponseCode(400)->setBody(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        } catch (\Exception $e) {
            return $this->response->setHttpResponseCode(500)->setBody(json_encode([
                'success' => false,
                'message' => __('Sale failed.')
            ]));
        }
    }

    /**
     * Validate payment data
     *
     * @param array $data
     * @throws LocalizedException
     */
    private function validatePaymentData(array $data)
    {
        $required = ['quote_id', 'card_data', 'amount', 'currency'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new LocalizedException(__('Required field missing: %1', $field));
            }
        }

        $cardRequired = ['cc_type', 'cc_number', 'cc_exp_month', 'cc_exp_year', 'cc_cid'];
        foreach ($cardRequired as $field) {
            if (!isset($data['card_data'][$field]) || empty($data['card_data'][$field])) {
                throw new LocalizedException(__('Required card field missing: %1', $field));
            }
        }
    }
}