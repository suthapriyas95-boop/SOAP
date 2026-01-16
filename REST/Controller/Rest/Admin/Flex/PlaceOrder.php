<?php
namespace CyberSource\Payment\Controller\Rest\Admin\Flex;

use CyberSource\Payment\Api\Admin\FlexOrderCreatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;

/**
 * Create orders using Flex microform tokens for admin
 */
class PlaceOrder extends \Magento\Framework\Webapi\Rest\Controller
{
    /**
     * @var FlexOrderCreatorInterface
     */
    private $flexOrderCreator;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param FlexOrderCreatorInterface $flexOrderCreator
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        FlexOrderCreatorInterface $flexOrderCreator,
        Request $request,
        Response $response
    ) {
        $this->flexOrderCreator = $flexOrderCreator;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Create order with Flex token
     *
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    public function execute()
    {
        try {
            $requestData = $this->request->getBodyParams();

            $quoteId = $requestData['quote_id'] ?? null;
            $token = $requestData['token'] ?? null;
            $cardData = $requestData['card_data'] ?? [];
            $orderData = $requestData['order_data'] ?? [];

            if (!$quoteId || !$token) {
                throw new LocalizedException(__('Quote ID and token are required.'));
            }

            $result = $this->flexOrderCreator->createOrder(
                $quoteId,
                $token,
                $cardData,
                $orderData
            );

            return $this->response->setHttpResponseCode(200)->setBody(json_encode($result));
        } catch (LocalizedException $e) {
            return $this->response->setHttpResponseCode(400)->setBody(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        } catch (\Exception $e) {
            return $this->response->setHttpResponseCode(500)->setBody(json_encode([
                'success' => false,
                'message' => __('An error occurred while creating order.')
            ]));
        }
    }
}