<?php

namespace CyberSource\AccountUpdater\Model\Report;

use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\Curl;
use CyberSource\Core\Model\LoggerInterface;
use CyberSource\AccountUpdater\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;

class Downloader implements DownloaderInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @param LoggerInterface $logger
     * @param Config $config
     * @param Filesystem $filesystem
     * @param Curl $curl
     */
    public function __construct(
        LoggerInterface $logger,
        Config $config,
        Filesystem $filesystem,
        Curl $curl
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->curl = $curl;
    }

    /**
     * @param string|null $reportDate
     * @return string
     * @throws \Exception
     */
    public function download($reportDate = '')
    {
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        // skipping download in test mode if test report path is defined
        if ($this->config->isTestMode() && $this->config->getTestReportPath()) {
            return $varDir->getAbsolutePath($this->config->getTestReportPath());
        }

        $varDir->create('au');

        $reportDate = $reportDate ? date('Y-m-d', strtotime($reportDate ?? '')) : date('Y-m-d');
        $reportPath = $varDir->getAbsolutePath("au/{$reportDate}-report.csv");

        $this->curl->setCredentials(
            $this->config->getUsername(),
            $this->config->getPassword()
        );

        $this->logger->info('pulling report ...');

        $this->curl->get(
            $this->getReportUrl($reportDate)
        );

        $reportContents = $this->curl->getBody() ?? '';

        // quick check if we actually got valid au report file
        if (strpos($reportContents, 'H,cybs.au.response.ss') !== 0) {
            throw new \Exception(
                'unable to pull report for ' . $reportDate
            );
        }

        if (! file_put_contents($reportPath, $reportContents)) {
            throw new \Exception(
                'unable to save report to ' . $reportPath
            );
        }

        return $reportPath;
    }

    /**
     * @param string|null $reportDate
     * @return string
     */
    private function getReportUrl($reportDate = null)
    {
        $endpointUrl = $this->config->getEndpointUrl();
        $merchantId = $this->config->getMerchantId();

        $reportDate = $reportDate ? date('Y/m/d', strtotime($reportDate ?? '')) : date('Y/m/d');
        $reportName = "{$merchantId}.au.response.ss.csv";

        return "{$endpointUrl}/{$reportDate}/{$merchantId}/{$reportName}";
    }
}
