<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

class PartnerInformationBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\Core\Model\Config
     */
    private $config;

    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\Core\Model\Config $config)
    {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        return [
            'clientReferenceInformation' => [
                'partner' => [
                    'developerId' => $this->config->getDeveloperId(),
                    'solutionId' => \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID,
                ]
            ]
        ];
    }
}
