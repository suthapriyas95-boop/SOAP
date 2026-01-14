<?php

namespace CyberSource\SecureAcceptance\Model\SecureToken;


/**
 * Class Generator
 * @package CyberSource\SecureAcceptance\Model\SecureToken
 */
class Generator
{
    const TOKEN_LENGTH = 32;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * Generator constructor.
     *
     * @param \Magento\Framework\Session\SessionManagerInterface $checkoutSession
     * @param \Magento\Framework\Math\Random $random
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $checkoutSession,
        \Magento\Framework\Math\Random $random,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->random = $random;
        $this->checkoutSession = $checkoutSession;
        $this->dateTime = $dateTime;
    }

    /**
     * @return string
     */
    public function get()
    {
        $tokenData = [
            'iat' => $this->dateTime->gmtTimestamp(),
            'value' => $this->random->getRandomString(self::TOKEN_LENGTH),
            'usages' => 0,
        ];

        $this->checkoutSession->setSecureToken($tokenData);

        return $tokenData['value'];
    }

}
