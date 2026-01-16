<?php
namespace CyberSource\Payment\Controller\Rest\Admin\Payment;

use CyberSource\Payment\Api\Admin\PaymentProcessorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;

/**
 * Void authorized transaction for admin orders
 */
class Void extends \Magento\Framework\Webapi\Rest\Controller
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
     * Void transaction
     *
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    public function execute()
    {
        try {
            $paymentData = $this->request->getBodyParams();

            // Validate required fields
            $this->validateVoidData($paymentData);

            $result = $this->paymentProcessor->void($paymentData);

            return $this->response->setHttpResponseCode(200)->setBody(json_encode($result));
        } catch (LocalizedException $e) {
            return $this->response->setHttpResponseCode(400)->setBody(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        } catch (\Exception $e) {
            return $this->response->setHttpResponseCode(500)->setBody(json_encode([
                'success' => false,
                'message' => __('Void failed.')
            ]));
        }
    }

    /**
     * Validate void data
     *
     * @param array $data
     * @throws LocalizedException
     */
    private function validateVoidData(array $data)
    {
        $required = ['order_id', 'transaction_id'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new LocalizedException(__('Required field missing: %1', $field));
            }
        }
    }
}