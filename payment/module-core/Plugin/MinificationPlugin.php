<?php

namespace CyberSource\Core\Plugin;

class MinificationPlugin
{
    /**
     * @var array
     */
    private $additionalExcludes = [];

    /**
     * @param array $additionalExcludes
     */
    public function __construct(array $additionalExcludes = [])
    {
        $this->additionalExcludes = $additionalExcludes;
    }

    /**
     * @param  \Magento\Framework\View\Asset\Minification $subject
     * @param  callable $proceed
     * @param  array $contentType
     * @return array $result
     */
    public function aroundGetExcludes(\Magento\Framework\View\Asset\Minification $subject, callable $proceed, $contentType)
    {
        $result = $proceed($contentType);
        if ($contentType != 'js') {
            return $result;
        }
        $result = array_merge($result, $this->additionalExcludes);
        return $result;
    }
}
