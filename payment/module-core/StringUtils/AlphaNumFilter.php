<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


class AlphaNumFilter implements FilterInterface
{

    public function filter($input)
    {
        return preg_replace("/[^[:alnum:][:space:]]/u", '', $input ?? '');
    }
}
