<?php

namespace CyberSource\Atp\Plugin;

use Magento\Framework\Registry;
use CyberSource\Atp\Model\Config;
use Magento\Customer\Api\Data\AddressInterface;
use CyberSource\Atp\Service\CyberSourceSoapAPI;
use Magento\Framework\Exception\LocalizedException;
use CyberSource\Atp\Model\DmeValidationResultFactory;
use CyberSource\Atp\Model\DmeResultPropagationManager;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use CyberSource\Atp\Model\Request\DmeAddressValidationDataBuilder;

class AddressRepositoryPlugin extends AbstractAtpPlugin
{
    /**
     * @var DmeAddressValidationDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @param Config $config
     * @param Registry $registry
     * @param CyberSourceSoapAPI $cyberSourceSoapClient
     * @param DmeAddressValidationDataBuilder $requestDataBuilder
     * @param DmeResultPropagationManager $eventPropagationManager
     * @param DmeValidationResultFactory $dmeResultFactory
     */
    public function __construct(
        Config $config,
        Registry $registry,
        CyberSourceSoapAPI $cyberSourceSoapClient,
        DmeAddressValidationDataBuilder $requestDataBuilder,
        DmeResultPropagationManager $eventPropagationManager,
        DmeValidationResultFactory $dmeResultFactory
    ) {
        parent::__construct(
            $config,
            $registry,
            $cyberSourceSoapClient,
            $eventPropagationManager,
            $dmeResultFactory
        );
        $this->requestDataBuilder = $requestDataBuilder;
    }

    /**
     * @param AddressRepository $subject
     * @param \Closure $proceed
     * @param AddressInterface $addressData
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundSave(
        AddressRepository $subject,
        \Closure $proceed,
        AddressInterface $addressData
    ) {
        if (! $this->canProcessAtpEvent()) {
            return $proceed($addressData);
        }

        $this->preventFurtherAtpProcessing();

        $type = CyberSourceSoapAPI::CS_EVENT_TYPE_UPDATE;

        $request = $this->requestDataBuilder->build($type, $addressData);

        $response = $this->cyberSourceSoapApi->call($request);

        /** @var \CyberSource\Atp\Model\DmeValidationResult $dmeValidationResult */
        $dmeValidationResult = $this->dmeResultFactory->create()
            ->setEventData(['object' => $addressData])
            ->setDecision($response->decision)
            ->setType($type);

        $this->eventPropagationManager->propagate($dmeValidationResult);

        // challenge is processed separately, no need to save data right now
        if ($dmeValidationResult->isChallenge()) {
            throw new LocalizedException(__('Challenge was requested'));
        }

        return $proceed($addressData);
    }
}
