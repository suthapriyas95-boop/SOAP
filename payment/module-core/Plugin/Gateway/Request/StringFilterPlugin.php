<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Plugin\Gateway\Request;

class StringFilterPlugin
{

    /**
     * @var \CyberSource\Core\StringUtils\FilterPoolInterface
     */
    private $filterPool;

    /**
     * @var \CyberSource\Core\StringUtils\RulePoolInterface
     */
    private $rulePool;

    public function __construct(
        \CyberSource\Core\StringUtils\FilterPoolInterface $filterPool,
        \CyberSource\Core\StringUtils\RulePoolInterface $rulePool
    ) {
        $this->filterPool = $filterPool;
        $this->rulePool = $rulePool;
    }

    public function afterBuild($subject, $result)
    {
        array_walk_recursive($result, [$this, 'filterField']);

        return $result;
    }


    /**
     * @param $value
     * @param $key
     *
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    private function filterField(&$value, $key)
    {
        if (!$this->rulePool->has($key)) {
            return $value;
        }
        $value = $this->filterPool->get(
            $this->rulePool->get($key)
        )
            ->filter($value);

        return $value;
    }

}
