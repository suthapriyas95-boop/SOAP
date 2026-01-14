<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;


class ItemIdDecorator implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \Magento\Framework\ObjectManager\TMap
     */
    private $builders;

    /**
     * @var string
     */
    private $prefix;

    public function __construct(
        \Magento\Framework\ObjectManager\TMapFactory $tmapFactory,
        array $builders = [],
        $prefix = 'item_'
    ) {

        $this->builders = $tmapFactory->create(
            [
                'array' => $builders,
                'type' => \Magento\Payment\Gateway\Request\BuilderInterface::class,
            ]
        );
        $this->prefix = $prefix;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $items = [];

        foreach ($this->builders as $builder) {
            $items = $this->merge($items, $builder->build($buildSubject));
        }

        $result = [];
        $i = 0;

        foreach ($items as $key => $item) {
            $prefix = $this->prefix . $i;
            foreach ($item as $k => $v) {
                $result[$prefix . '_' . $k] = $v;
            }
            $i++;
        }

        $result['line_item_count'] = count($items);

        return $result;
    }

    private function merge(array $result, array $builder)
    {
        return array_merge($result, $builder);
    }
}
