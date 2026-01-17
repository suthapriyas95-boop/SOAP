<?php
namespace CyberSource\Payment\Controller\Rest\Admin\Token;

use CyberSource\Payment\Api\Admin\TokenGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;

/**
 * Generate Flex microform tokens for admin orders
 */
class Generate extends \Magento\Framework\Webapi\Rest\Controller
{
    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param TokenGeneratorInterface $tokenGenerator
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        TokenGeneratorInterface $tokenGenerator,
        Request $request,
        Response $response
    ) {
        $this->tokenGenerator = $tokenGenerator;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Generate Flex token
     *
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    public function execute()
    {
        try {
            $requestData = $this->request->getBodyParams();
            $quoteId = $requestData['quote_id'] ?? null;
            $storeId = $requestData['store_id'] ?? null;

            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID is required.'));
            }

            $result = $this->tokenGenerator->generateToken($quoteId, $storeId);

            return $this->response->setHttpResponseCode(200)->setBody(json_encode($result));
        } catch (LocalizedException $e) {
            return $this->response->setHttpResponseCode(400)->setBody(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        } catch (\Exception $e) {
            return $this->response->setHttpResponseCode(500)->setBody(json_encode([
                'success' => false,
                'message' => __('An error occurred while generating token.')
            ]));
        }
    }
}