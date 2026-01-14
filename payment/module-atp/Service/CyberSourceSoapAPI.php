<?php

namespace CyberSource\Atp\Service;

use CyberSource\Atp\Model\Config;
use CyberSource\Core\Model\LoggerInterface;
use CyberSource\Core\Service\AbstractConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CyberSourceSoapAPI extends AbstractConnection
{
    const VALID_REASON_CODES = [100, 481, 482]; // ACCEPT, REJECT, CHALLENGE

    const CS_EVENT_TYPE_LOGIN = 'login';
    const CS_EVENT_TYPE_CREATION = 'account_creation';
    const CS_EVENT_TYPE_UPDATE = 'account_update';

    /**
     * @var Config
     */
    private $atpConfig;

    /**
     * @var LoggerInterface
     */
    private $atpLogger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param LoggerInterface $atpLogger
     * @param Config $atpConfig
     * @throws \Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        LoggerInterface $atpLogger,
        Config $atpConfig
    ) {
        parent::__construct($scopeConfig, $logger);

        $this->atpConfig = $atpConfig;
        $this->atpLogger = $atpLogger;
    }

    /**
     * @param \stdClass $request
     * @return \stdClass
     */
    public function call($request)
    {
        try {
            $result = $this->client->runTransaction($request);

            $dataToLog = [
                'request' => $request,
                'response' => $result,
                'client' => static::class
            ];

            $this->atpLogger->debug($dataToLog);

            if (!$result || !in_array($result->reasonCode, self::VALID_REASON_CODES)) {
                throw new LocalizedException(__('Something went wrong. Default action will be taken'));
            }

            return $result;
        } catch (\Exception $e) {
            $this->atpLogger->error($e->getMessage());

            $result = new \stdClass();
            $result->decision = $this->atpConfig->getActionOnError();

            return $result;
        }
    }
}
