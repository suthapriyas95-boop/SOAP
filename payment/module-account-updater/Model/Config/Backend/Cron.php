<?php

namespace CyberSource\AccountUpdater\Model\Config\Backend;

use Magento\Framework\Registry;
use Magento\Framework\Model\Context;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use CyberSource\AccountUpdater\Model\Config\Backend\Cron\Validator;

class Cron extends \Magento\Framework\App\Config\Value
{
    const CRON_STRING_PATH = 'crontab/default/jobs/cs_account_updater/schedule/cron_expr';

    const XML_PATH_AU_ACTIVE = 'groups/cybersource_section/groups/cybersource_au/fields/active/value';
    const XML_PATH_AU_CRON_EXPR = 'groups/cybersource_section/groups/cybersource_au/fields/cron_expr/value';

    const DEFAULT_CRON_VALUE = '0 0 1 * *';
    /**
     * @var ValueFactory
     */
    private $configValueFactory;

    /**
     * @var Validator
     */
    private $exprValidator;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param Validator $validator
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $configValueFactory,
        Validator $validator,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configValueFactory = $configValueFactory;
        $this->exprValidator = $validator;

        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function beforeSave()
    {
        if (!$this->getValue()) {
            $this->setValue(self::DEFAULT_CRON_VALUE);
        }
        return parent::beforeSave();
    }

    /**
     * @throws LocalizedException
     */
    public function afterSave()
    {
        if (! $this->getData(self::XML_PATH_AU_ACTIVE)) {
            return parent::afterSave();
        }

        $cronExprString = $this->getValue();

        try {
            if (! $this->exprValidator->validate($cronExprString)) {
                throw new LocalizedException(__('AU cron expression format is invalid'));
            }

            $this->configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LocalizedException(__('can\'t save AU cron expression.'));
        }

        return parent::afterSave();
    }
}
