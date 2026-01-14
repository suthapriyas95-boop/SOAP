<?php

namespace CyberSource\Atp\Plugin;

use Magento\Framework\Registry;
use CyberSource\Atp\Model\Config;
use Magento\Customer\Model\Session;
use CyberSource\Atp\Service\CyberSourceSoapAPI;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use CyberSource\Atp\Model\DmeValidationResultFactory;
use CyberSource\Atp\Model\DmeResultPropagationManager;
use CyberSource\Atp\Model\Request\DmeValidationDataBuilder;

class CustomerSessionPlugin extends AbstractAtpPlugin
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
     * @param Session $subject
     * @param \Closure $proceed
     * @param CustomerInterface $customerData
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundSetCustomerDataAsLoggedIn(
        Session $subject,
        \Closure $proceed,
        CustomerInterface $customerData
    ) {

        if (! $this->canProcessAtpEvent()) {
            return $proceed($customerData);
        }

        $this->preventFurtherAtpProcessing();

        $type = CyberSourceSoapAPI::CS_EVENT_TYPE_LOGIN;

        $request = $this->requestDataBuilder->build($type, $customerData);

        $response = $this->cyberSourceSoapApi->call($request);

        /** @var \CyberSource\Atp\Model\DmeValidationResult $dmeValidationResult */
        $dmeValidationResult = $this->dmeResultFactory->create()
            ->setEventData(['object' => $customerData])
            ->setDecision($response->decision)
            ->setType($type);

        $this->eventPropagationManager->propagate($dmeValidationResult);

        // challenge is processed separately, no need to login customer right now
        if ($dmeValidationResult->isChallenge()) {
            throw new LocalizedException(__('Challenge was requested'));
        }

        return $proceed($customerData);
    }
}
