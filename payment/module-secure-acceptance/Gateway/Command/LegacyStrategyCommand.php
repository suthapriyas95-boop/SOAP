<?php

namespace CyberSource\SecureAcceptance\Gateway\Command;

use CyberSource\Core\Model\Logger;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader;
use CyberSource\SecureAcceptance\Gateway\Config\Config;

class LegacyStrategyCommand implements CommandInterface
{
    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var String
     */
    private $command;

    /**
     * @var String
     */
    private $legacyCommand;

    /**
     * @var bool
     */
    private $isAdmin;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CaptureStrategyCommand constructor.
     * @param CommandPoolInterface $commandPool
     * @param SubjectReader $subjectReader
     * @param Config $config
     * @param string|null $command
     * @param string|null $legacyCommand
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        SubjectReader $subjectReader,
        Config $config,
        ?string $command = null,
        ?string $legacyCommand = null,
        bool $isAdmin = false
    ) {
        $this->commandPool = $commandPool;
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->command = $command;
        $this->legacyCommand = $legacyCommand;
        $this->isAdmin = $isAdmin;
    }

    public function execute(array $commandSubject)
    {
        //TODO: FLEX can only flow through SOAP flow, excluding admin
        if (
            !$this->isAdmin
            && (!$this->config->getIsLegacyMode() || $this->config->isMicroform())
        ) {
            $this->commandPool->get($this->command)->execute($commandSubject);
            return;
        }
        else if($this->isAdmin && $this->config->isMicroform())
        {
            $commandResult = $this->commandPool->get($this->command)->execute($commandSubject);
            return;
        }
        
        $this->commandPool->get($this->legacyCommand)->execute($commandSubject);
    }
}
