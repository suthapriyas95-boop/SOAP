<?php
/**
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


class MaxLengthFilter implements FilterInterface
{

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $stringUtils;

    /**
     * @var int
     */
    private $maxLength;

    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        $maxLength = 255
    ) {
        $this->stringUtils = $stringUtils;
        $this->maxLength = (int)$maxLength;
    }

    /**
     * @inheritDoc
     */
    public function filter($input)
    {
        return $this->stringUtils->substr($input ?? '', 0, $this->maxLength);
    }
}
