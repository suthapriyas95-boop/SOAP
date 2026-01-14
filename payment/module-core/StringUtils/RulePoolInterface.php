<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


interface RulePoolInterface
{

    /**
     * Checks the existence of the rule in the pool
     *
     * @param $code
     *
     * @return bool
     */
    public function has($code);

    /**
     * Gets the rule from the pool
     *
     * @param $code
     *
     * @return string
     */
    public function get($code);
}
