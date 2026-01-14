<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


class RulePool implements RulePoolInterface
{

    /**
     * @var string[]
     */
    private $rules;

    public function __construct($rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * @inheritDoc
     */
    public function has($code)
    {
        return isset($this->rules[$code]);
    }

    /**
     * @inheritDoc
     */
    public function get($code)
    {
        if (!isset($this->rules[$code])) {
            throw new \Magento\Framework\Exception\NotFoundException(__('The string filter rule "%1" doesn\'t exist.', $code));
        }

        return $this->rules[$code];
    }
}
