<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\AccountUpdater\Model\Report;


class DownloaderSfs implements DownloaderInterface
{

    const REPORT_FILE_NAME_PATTERN = 'au.response.ss.csv';

    /**
     * @var \Magento\Payment\Gateway\CommandInterface
     */
    private $getFileListCommand;

    /**
     * @var \Magento\Payment\Gateway\CommandInterface
     */
    private $getFileCommand;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \CyberSource\AccountUpdater\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * DownloaderSfs constructor.
     *
     * @param \CyberSource\AccountUpdater\Model\Config $config
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Payment\Gateway\CommandInterface $getFileListCommand
     * @param \Magento\Payment\Gateway\CommandInterface $getFileCommand
     */
    public function __construct(
        \CyberSource\AccountUpdater\Model\Config $config,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Payment\Gateway\CommandInterface $getFileListCommand,
        \Magento\Payment\Gateway\CommandInterface $getFileCommand
    ) {
        $this->getFileListCommand = $getFileListCommand;
        $this->getFileCommand = $getFileCommand;
        $this->dateTime = $dateTime;
        $this->config = $config;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritDoc
     */
    public function download($reportDate = null)
    {

        $varDir = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);

        // skipping download in test mode if test report path is defined
        if ($this->config->isTestMode() && $this->config->getTestReportPath()) {
            return $varDir->getAbsolutePath($this->config->getTestReportPath());
        }

        $varDir->create('au');

        $reportDate = $reportDate ?? $this->dateTime->gmtDate('Y-m-d');

        $reportPath = $varDir->getAbsolutePath("au/{$reportDate}-report.csv");

        $filesList = $this->getFileListCommand->execute([
            'startDate' => $reportDate,
            'endDate' => $reportDate
        ])
            ->get();

        $auReportFileId = null;

        foreach ($filesList['fileDetails'] as $fileInfo) {
            if (stripos($fileInfo['name'], static::REPORT_FILE_NAME_PATTERN) !== false ) {
                $auReportFileId = $fileInfo['fileId'];
                break;
            }
        }

        if (!$auReportFileId) {
            throw new \Exception('Unable to find a report for ' . $reportDate);
        }

        $fileGetResult = $this->getFileCommand->execute([
            'fileId' => $auReportFileId,
        ])->get();

        $reportContents = $fileGetResult['response'] ?? '';

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
}
