<?php
namespace CyberSource\Payment\Controller\Rest\Admin\Sop;

use CyberSource\Payment\Api\Admin\SopResponseHandlerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;

/**
 * Handle SOP responses for admin orders
 */
class Response extends \Magento\Framework\Webapi\Rest\Controller
{
    /**
     * @var SopResponseHandlerInterface
     */
    private $sopResponseHandler;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param SopResponseHandlerInterface $sopResponseHandler
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        SopResponseHandlerInterface $sopResponseHandler,
        Request $request,
        Response $response
    ) {
        $this->sopResponseHandler = $sopResponseHandler;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Handle SOP response
     *
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    public function execute()
    {
        try {
            // Get response data from POST body (CyberSource callback)
            $responseData = $this->request->getBodyParams();

            // Get order data from session or additional parameters
            $orderData = $this->request->getParam('order_data', []);

            $result = $this->sopResponseHandler->handleResponse($responseData, $orderData);

            return $this->response->setHttpResponseCode(200)->setBody(json_encode($result));
        } catch (LocalizedException $e) {
            return $this->response->setHttpResponseCode(400)->setBody(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        } catch (\Exception $e) {
            return $this->response->setHttpResponseCode(500)->setBody(json_encode([
                'success' => false,
                'message' => __('An error occurred while processing response.')
            ]));
        }
    }
}