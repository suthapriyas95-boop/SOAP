<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;


class DateIntervalBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    const REPORT_INTERVAL = 24 * 3600;
    const DATE_FORMAT = 'Y-m-d';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->dateTime = $dateTime;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $gmtTimestamp = $this->dateTime->gmtTimestamp();
        $interval = $buildSubject['interval'] ?? static::REPORT_INTERVAL;

        $startDateTime = $buildSubject['startDate']
            ?? $this->dateTime->gmtDate(static::DATE_FORMAT, $gmtTimestamp - $interval);

        $endDateTime = $buildSubject['startDate']
            ?? $this->dateTime->gmtDate(static::DATE_FORMAT, $gmtTimestamp);

        return [
            'startDate' => $startDateTime,
            'endDate' => $endDateTime,
        ];
    }
}
