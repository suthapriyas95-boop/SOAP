<?php

namespace CyberSource\AccountUpdater\Model\Report;

use Magento\Framework\Filesystem;
use Magento\Framework\Api\FilterBuilder;
use CyberSource\Core\Model\LoggerInterface;
use CyberSource\AccountUpdater\Model\Config;
use Magento\Vault\Model\PaymentTokenRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Processor
{
    const AU_CODE_SUCCESS = 800;

    const AU_NO_UPDATE = 'NUP';
    const AU_NEW_CC_NUM = 'NAN';
    const AU_NEW_EXP_DATE = 'NED';
    const AU_CARD_CURRENT = 'CUR';
    const AU_ACCOUNT_CLOSED = 'ACL';
    const AU_NOT_APPLICABLE = 'UNA';
    const AU_ERROR_PROCESSING = 'ERR';
    const AU_CONTACT_CARDHOLDER = 'CCH';

    private $result = [
        'updated' => 0,
        'deleted' => 0,
        'skipped' => 0,
        'failed' => 0
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var PaymentTokenRepository
     */
    private $tokenRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param LoggerInterface $logger
     * @param Config $config
     * @param Filesystem $filesystem
     * @param FilterBuilder $filterBuilder
     * @param PaymentTokenRepository $tokenRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        LoggerInterface $logger,
        Config $config,
        Filesystem $filesystem,
        FilterBuilder $filterBuilder,
        PaymentTokenRepository $tokenRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->filterBuilder = $filterBuilder;
        $this->tokenRepository = $tokenRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function process($filePath)
    {
        $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);

        if (! $varDir->isReadable($filePath)) {
            $this->logger->critical($filePath . ' is not readable');
            throw new \Exception('report is not readable');
        }

        $file = $varDir->openFile($filePath);

        while (($csvRow = $file->readCsv()) !== false) {
            // skipping header and footer
            if ($csvRow[0] != 'D') {
                continue;
            }
            $this->updateToken(
                $this->formatRowData($csvRow)
            );
        }

        return $this->result;
    }

    /**
     * @param array $data
     */
    private function updateToken($data)
    {
        try {
            $this->logger->info('processing token with profile id ' . $data['profileId']);

            if (! $token = $this->getTokenByProfileId($data['profileId'])) {
                throw new \Exception('token was not found');
            }

            switch ($data['responseCode']) {
                case self::AU_NEW_CC_NUM:
                case self::AU_NEW_EXP_DATE:
                    $cardDetails = \Laminas\Json\Json::decode($token->getTokenDetails());
                    $cardDetails['type'] = $data['type'];
                    $cardDetails['maskedCC'] = $data['maskedCC'];
                    $cardDetails['expirationDate'] = $data['expirationDate'];
                    $cardDetails['auRequestId'] = $data['auRequestId'];

                    $token->setTokenDetails(\Laminas\Json\Json::encode($cardDetails));
                    if ($newProfileId = $data['newProfileId']) {
                        $token->setGatewayToken($newProfileId);
                    }

                    $this->tokenRepository->save($token);
                    $this->result['updated']++;
                    break;

                case self::AU_ACCOUNT_CLOSED:
                case self::AU_CONTACT_CARDHOLDER:
                    $this->tokenRepository->delete($token);
                    $this->logger->info('token was deleted with response code ' . $data['responseCode']);
                    $this->result['deleted']++;
                    break;

                case self::AU_CARD_CURRENT:
                case self::AU_NOT_APPLICABLE:
                case self::AU_NO_UPDATE:
                    $this->logger->info('token was skipped with response code ' . $data['responseCode']);
                    $this->result['skipped']++;
                    break;

                case self::AU_ERROR_PROCESSING:
                    throw new \Exception('failed with reason code ' . $data['reasonCode']);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->result['failed']++;
        }
    }

    /**
     * @param string $row
     * @return array
     */
    private function formatRowData($row)
    {
        $formattedRowData = [];

        $formattedRowData['auRequestId'] = $row[1];
        $formattedRowData['profileId'] = $row[2];
        $formattedRowData['responseCode'] = $row[4]; // NAN, NED, ACL etc
        $formattedRowData['reasonCode'] = $row[5]; // 800, 801, 802 etc
        $formattedRowData['type'] = substr($row[3] ?? '', 0, 1) == 4 ? 'VI' : 'MC';
        $formattedRowData['maskedCC'] = '****-****-****-' . substr($row[3] ?? '', -4);
        $formattedRowData['expirationDate'] = $row[9] . '/20' . $row[10];
        $formattedRowData['newProfileId'] = isset($row[11]) ? $row[11] : false;

        return $formattedRowData;
    }

    /**
     * @param string $profileId
     * @return PaymentTokenInterface|null
     */
    private function getTokenByProfileId($profileId)
    {
        $transactionSearchCriteria = $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('gateway_token')
                    ->setValue($profileId)
                    ->create()
            ]
        )->create();

        $items = $this->tokenRepository->getList($transactionSearchCriteria)->getItems();
        return array_shift($items);
    }
}
