<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;


use CyberSource\Core\Model\Config;

class TimeIntervalBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    const REPORT_INTERVAL = 23 * 3600;
    const DATE_FORMAT = 'c';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        Config $config
    ) {
        $this->dateTime = $dateTime;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $interval = $buildSubject['interval'] ?? static::REPORT_INTERVAL;

        if ($buildSubject['startTime'] != null) {
            $startDateTime = $this->dateTime->gmtDate(static::DATE_FORMAT, $buildSubject['startTime']);
            $calculatedStartTime = $this->dateTime->gmtTimestamp($startDateTime);
            $endDateTime = $this->dateTime->gmtDate(static::DATE_FORMAT, $calculatedStartTime + $interval);
        }
        else {
            $gmtTimestamp = $this->dateTime->gmtTimestamp();

            $startDateTime = $buildSubject['startTime']
                ?? $this->dateTime->gmtDate(static::DATE_FORMAT, $gmtTimestamp - $interval);
            $endDateTime = $buildSubject['endTime']
                ?? $this->dateTime->gmtDate(static::DATE_FORMAT, $gmtTimestamp);
        }


        return [
            'startTime' => $startDateTime,
            'endTime' => $endDateTime,
        ];
    }
}
