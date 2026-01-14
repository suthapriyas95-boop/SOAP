<?php

namespace CyberSource\SecureAcceptance\Service;

class Lock
{

    const LOCK_TIMEOUT = 5;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * Lock constructor.
     * @param \Magento\Framework\App\ResourceConnection $connection
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $connection
    ) {
        $this->connection = $connection->getConnection();
    }

    public function acquireLock($name)
    {
        $query = "SELECT GET_LOCK(:name, :timeout)";
        $queryResult = $this->connection->fetchOne($query, ['name' => $name, 'timeout' => self::LOCK_TIMEOUT]);
        return $queryResult === '1';
    }

    public function releaseLock($name)
    {
        $query = "SELECT RELEASE_LOCK(:name)";
        $this->connection->fetchOne($query, ['name' => $name]);
    }
}
