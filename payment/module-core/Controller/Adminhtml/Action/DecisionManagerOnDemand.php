<?php


namespace CyberSource\Core\Controller\Adminhtml\Action;


use CyberSource\Core\Cron\DecisionManagerReport;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class DecisionManagerOnDemand extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var DecisionManagerReport
     */
    private $decisionManagerReport;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        DecisionManagerReport $decisionManagerReport,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        JsonFactory $jsonResultFactory
    ) {
        parent::__construct($context);

        $this->decisionManagerReport = $decisionManagerReport;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->resultJsonFactory = $jsonResultFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return ResultInterface|ResponseInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();


        $reportDate = $this->getRequest()->getParam('date');

        $storeId = $this->storeManager->getStore()->getId();

        try {
            $this->decisionManagerReport->runReport($storeId, $reportDate);

        } catch(\Exception $e) {
            $this->logger->critical('Error');
        } finally {
            if (!empty($this->decisionManagerReport->getExceptions())) {
                $data = $this->decisionManagerReport->getExceptions();
            } else {
                $data = 'Success';
            }

            $result->setData([
                'status' => true,
                'data' => $data
            ]);
        }

        return $result;

    }
}
