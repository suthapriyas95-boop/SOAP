<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Model\Vault\Observer;

class PaymentTokenAssignerPlugin
{
    public function aroundExecute(
        \Magento\Vault\Observer\PaymentTokenAssigner $subject,
        callable $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        $event = $observer->getEvent();
        $paymentModel = $event->getDataByKey(\Magento\Payment\Observer\AbstractDataAssignObserver::MODEL_CODE);

        if (!$paymentModel) {
            return $proceed($observer);
        }

        $cardType = $paymentModel->getAdditionalInformation('cardType');
        $referenceId = $paymentModel->getAdditionalInformation(\CyberSource\ThreeDSecure\Gateway\Command\Cca\PayerAuthSetUpBuilderCommand::KEY_PAYER_AUTH_ENROLL_REFERENCE_ID);
        if (!$referenceId) {
            return $proceed($observer);
        }
        $result = $proceed($observer);

        $paymentModel->setAdditionalInformation(
            \CyberSource\ThreeDSecure\Gateway\Command\Cca\PayerAuthSetUpBuilderCommand::KEY_PAYER_AUTH_ENROLL_REFERENCE_ID,
            $referenceId
        );

        if ($cardType) {
            $paymentModel->setAdditionalInformation('cardType', $cardType);
        }

        return $result;
    }
}
