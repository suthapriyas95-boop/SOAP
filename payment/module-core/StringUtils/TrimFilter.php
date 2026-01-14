<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


class TrimFilter implements FilterInterface
{

    /**
     * @inheritDoc
     */
    public function filter($input)
    {
        return trim($input ?? '');
    }
}
