<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\StringUtils;


class FilterPool implements FilterPoolInterface
{

    /**
     * @var \CyberSource\Core\StringUtils\FilterInterface[]
     */
    private $filters;

    public function __construct(
        \Magento\Framework\ObjectManager\TMapFactory $tmapFactory,
        array $filters = []
    ) {
        $this->filters = $tmapFactory->create(
            [
                'array' => $filters,
                'type' => \CyberSource\Core\StringUtils\FilterInterface::class,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function get($code)
    {
        if (!isset($this->filters[$code])) {
            throw new \Magento\Framework\Exception\NotFoundException(__('The filter "%1" doesn\'t exist.', $code));
        }

        return $this->filters[$code];
    }
}
