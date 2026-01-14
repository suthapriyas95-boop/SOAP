<?php

namespace CyberSource\Atp\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use CyberSource\Atp\Model\Config;
use CyberSource\Atp\Service\CyberSourceSoapAPI;
use Magento\Customer\Api\Data\CustomerInterface;
use CyberSource\Atp\Model\DmeValidationResultFactory;
use CyberSource\Atp\Model\DmeResultPropagationManager;
use CyberSource\Atp\Model\Request\DmeValidationDataBuilder;
use Magento\Customer\Model\ResourceModel\CustomerRepository;

class CustomerRepositoryPlugin extends AbstractAtpPlugin
{
    /**
     * @var DmeValidationDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @param Config $config
     * @param Registry $registry
     * @param CyberSourceSoapAPI $cyberSourceSoapClient
     * @param DmeValidationDataBuilder $requestDataBuilder
     * @param DmeResultPropagationManager $eventPropagationManager
     * @param DmeValidationResultFactory $dmeResultFactory
     */
    public function __construct(
        Config $config,
        Registry $registry,
        CyberSourceSoapAPI $cyberSourceSoapClient,
        DmeValidationDataBuilder $requestDataBuilder,
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
     * @param CustomerRepository $subject
     * @param \Closure $proceed
     * @param CustomerInterface $customerData
     * @param null $passwordHash
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundSave(
        CustomerRepository $subject,
        \Closure $proceed,
        CustomerInterface $customerData,
        $passwordHash = null
    ) {
        if (! $this->canProcessAtpEvent()) {
            return $proceed($customerData, $passwordHash);
        }

        $this->preventFurtherAtpProcessing();

        $type = $customerData->getId()
            ? CyberSourceSoapAPI::CS_EVENT_TYPE_UPDATE
            : CyberSourceSoapAPI::CS_EVENT_TYPE_CREATION;

        $request = $this->requestDataBuilder->build($type, $customerData);

        $response = $this->cyberSourceSoapApi->call($request);

        /** @var \CyberSource\Atp\Model\DmeValidationResult $dmeValidationResult */
        $dmeValidationResult = $this->dmeResultFactory->create()
            ->setEventData(['object' => $customerData, 'password_hash' => $passwordHash])
            ->setDecision($response->decision)
            ->setType($type);

        $this->eventPropagationManager->propagate($dmeValidationResult);

        // challenge is processed separately, no need to save data right now
        if ($dmeValidationResult->isChallenge()) {
            throw new LocalizedException(__('Challenge was requested'));
        }

        return $proceed($customerData, $passwordHash);
    }
}
