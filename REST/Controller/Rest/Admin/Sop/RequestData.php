<?php
namespace CyberSource\Payment\Controller\Rest\Admin\Sop;

use CyberSource\Payment\Api\Admin\SopRequestDataBuilderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;

/**
 * Build SOP request data for admin orders
 */
class RequestData extends \Magento\Framework\Webapi\Rest\Controller
{
    /**
     * @var SopRequestDataBuilderInterface
     */
    private $sopRequestDataBuilder;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param SopRequestDataBuilderInterface $sopRequestDataBuilder
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        SopRequestDataBuilderInterface $sopRequestDataBuilder,
        Request $request,
        Response $response
    ) {
        $this->sopRequestDataBuilder = $sopRequestDataBuilder;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Build SOP request data
     *
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    public function execute()
    {
        try {
            $requestData = $this->request->getBodyParams();

            // Validate request data
            $this->sopRequestDataBuilder->validateRequestData($requestData);

            $quoteId = $requestData['quote_id'];
            $cardType = $requestData['cc_type'];
            $vaultEnabled = $requestData['vault_enabled'] ?? false;
            $storeId = $requestData['store_id'] ?? null;

            $result = $this->sopRequestDataBuilder->buildRequestData(
                $quoteId,
                $cardType,
                $vaultEnabled,
                $storeId
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
                'message' => __('An error occurred while building request data.')
            ]));
        }
    }
}