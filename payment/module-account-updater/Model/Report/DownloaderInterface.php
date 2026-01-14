<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\AccountUpdater\Model\Report;

interface DownloaderInterface
{
    /**
     * @param string|null $reportDate
     *
     * @return string Path to report file
     * @throws \Exception
     */
    public function download($reportDate = null);
}
