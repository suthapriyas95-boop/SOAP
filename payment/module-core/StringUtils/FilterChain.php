<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


class FilterChain implements FilterInterface
{
    /**
     * @var \CyberSource\Core\StringUtils\FilterInterface[]
     */
    private $filters;

    /**
     * @param \Magento\Framework\ObjectManager\TMapFactory $tmapFactory
     * @param array $filters
     */
    public function __construct(
        \Magento\Framework\ObjectManager\TMapFactory $tmapFactory,
        array $filters = []
    ) {
        $this->filters = $tmapFactory->create(
            [
                'array' => $filters,
                'type' => FilterInterface::class,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function filter($input)
    {
        foreach ($this->filters as $filter) {
            $input = $filter->filter($input);
        }

        return $input;
    }
}
