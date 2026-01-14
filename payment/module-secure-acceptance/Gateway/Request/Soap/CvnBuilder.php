<?php

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

class CvnBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    const ADMIN_PREFIX = 'admin';

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $session;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var string
     */
    private $isAdmin;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        ?string $isAdmin = null
    ) {
        $this->subjectReader = $subjectReader;
        $this->session = $session;
        $this->config = $config;
        $this->isAdmin = $isAdmin;
    }

    /**
     * Builds Subscription data request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $result = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        if (!$cvv = $payment->getAdditionalInformation('cvv') ?? $this->session->getData('cvv')) {
            return [];
        }

        $prefix = '';

        if ($this->isAdmin) {
            $prefix = self::ADMIN_PREFIX . '_';
        }

        $path = 'enable_' . $prefix . 'cvv';

        if ($this->config->getValue($path) || !$this->config->isMicroform()) {
            $result['card']['cvNumber'] = $cvv;
        }

        $payment->unsAdditionalInformation('cvv');

        return $result;
    }
}
