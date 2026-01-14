<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


interface FilterInterface
{

    /**
     * @param string $input
     *
     * @return string
     */
    public function filter($input);

}
