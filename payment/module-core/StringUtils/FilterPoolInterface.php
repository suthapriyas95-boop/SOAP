<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


interface FilterPoolInterface
{
    /**
     * @param string $code
     *
     * @return \CyberSource\Core\StringUtils\FilterInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function get($code);
}
