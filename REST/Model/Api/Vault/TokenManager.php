<?php
namespace CyberSource\Payment\Model\Api\Vault;

use CyberSource\Payment\Api\Admin\Vault\TokenManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory;

/**
 * Manage vault tokens for admin orders
 */
class TokenManager implements TokenManagerInterface
{
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var PaymentTokenInterfaceFactory
     */
    private $paymentTokenFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CollectionFactory
     */
    private $tokenCollectionFactory;

    /**
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenInterfaceFactory $paymentTokenFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CollectionFactory $tokenCollectionFactory
     */
    public function __construct(
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        CustomerRepositoryInterface $customerRepository,
        CollectionFactory $tokenCollectionFactory
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->customerRepository = $customerRepository;
        $this->tokenCollectionFactory = $tokenCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function saveToken(array $paymentData, array $transactionData)
    {
        try {
            $customerId = $paymentData['customer_id'] ?? null;
            if (!$customerId) {
                throw new LocalizedException(__('Customer ID is required to save token.'));
            }

            $customer = $this->customerRepository->getById($customerId);

            // Create payment token
            $paymentToken = $this->paymentTokenFactory->create();
            $paymentToken->setCustomerId($customerId);
            $paymentToken->setPaymentMethodCode('cybersource');
            $paymentToken->setType('card');
            $paymentToken->setGatewayToken($transactionData['subscription_id'] ?? '');
            $paymentToken->setPublicHash($this->generatePublicHash());
            $paymentToken->setIsActive(true);
            $paymentToken->setIsVisible(true);

            // Set token details
            $paymentToken->setTokenDetails(json_encode([
                'cc_type' => $paymentData['cc_type'] ?? '',
                'cc_exp_month' => $paymentData['cc_exp_month'] ?? '',
                'cc_exp_year' => $paymentData['cc_exp_year'] ?? '',
                'cc_last4' => substr($paymentData['cc_number'] ?? '', -4)
            ]));

            $this->paymentTokenRepository->save($paymentToken);

            return $paymentToken->getPublicHash();
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to save payment token: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function getCustomerTokens($customerId)
    {
        $collection = $this->tokenCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('payment_method_code', 'cybersource');
        $collection->addFieldToFilter('is_active', 1);

        $tokens = [];
        foreach ($collection as $token) {
            $tokens[] = [
                'public_hash' => $token->getPublicHash(),
                'cc_type' => $token->getType(),
                'cc_last4' => json_decode($token->getTokenDetails(), true)['cc_last4'] ?? '',
                'cc_exp_month' => json_decode($token->getTokenDetails(), true)['cc_exp_month'] ?? '',
                'cc_exp_year' => json_decode($token->getTokenDetails(), true)['cc_exp_year'] ?? ''
            ];
        }

        return $tokens;
    }

    /**
     * @inheritdoc
     */
    public function deleteToken($publicHash)
    {
        try {
            $token = $this->paymentTokenRepository->getById($publicHash);
            if ($token) {
                $this->paymentTokenRepository->delete($token);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to delete token: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateTokenForCustomer($publicHash, $customerId)
    {
        try {
            $token = $this->paymentTokenRepository->getById($publicHash);
            return $token && $token->getCustomerId() == $customerId && $token->getIsActive();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate unique public hash for token
     *
     * @return string
     */
    private function generatePublicHash()
    {
        return hash('sha256', uniqid('cybersource_', true));
    }
}