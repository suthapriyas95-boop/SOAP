<?php

namespace CyberSource\AccountUpdater\Controller\Adminhtml\Action;

use Magento\Backend\App\Action\Context;
use CyberSource\AccountUpdater\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\JsonFactory;
use CyberSource\AccountUpdater\Model\Report\Processor;
use CyberSource\AccountUpdater\Model\Report\Downloader;

class Update extends \Magento\Backend\App\Action
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \CyberSource\AccountUpdater\Model\Report\DownloaderInterface
     */
    private $reportDownloader;

    /**
     * @var Processor
     */
    private $reportProcessor;

    /**
     * @param Context $context
     * @param Config $config
     * @param JsonFactory $jsonResultFactory
     * @param \CyberSource\AccountUpdater\Model\Report\DownloaderInterface $reportDownloader
     * @param Processor $reportProcessor
     */
    public function __construct(
        Context $context,
        Config $config,
        JsonFactory $jsonResultFactory,
        \CyberSource\AccountUpdater\Model\Report\DownloaderInterface $reportDownloader,
        Processor $reportProcessor
    ) {
        parent::__construct($context);

        $this->config = $config;
        $this->resultJsonFactory = $jsonResultFactory;
        $this->reportDownloader = $reportDownloader;
        $this->reportProcessor = $reportProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            if (! $this->getRequest()->isPost()) {
                throw new LocalizedException(__('Only POST requests accepted'));
            }

            if (! $this->config->isActive()) {
                throw new LocalizedException(__('Account Updater is disabled'));
            }

            $reportDate = $this->getRequest()->getParam('date');
            $reportFile = $this->reportDownloader->download($reportDate);
            $processResult = $this->reportProcessor->process($reportFile);

            $result->setData([
                'status' => true,
                'data' => $processResult
            ]);
        } catch (\Exception $e) {
            $result->setData([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
