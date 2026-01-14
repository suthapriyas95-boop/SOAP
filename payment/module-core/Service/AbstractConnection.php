<?php
namespace CyberSource\Core\Service;

use CyberSource\Core\Model\DoRequest;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use InvalidArgumentException;
use SoapClient;

abstract class AbstractConnection
{
    const XML_NAMESPACE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const IS_TEST_MODE_CONFIG_PATH = 'payment/chcybersource/use_test_wsdl';
    const TEST_WSDL_PATH = 'payment/chcybersource/path_to_test_wsdl';
    const LIVE_WSDL_PATH = 'payment/chcybersource/path_to_wsdl';

    const MERCHANT_ID_PATH = 'payment/chcybersource/merchant_id';
    const P12_ACCESSKEY = 'payment/chcybersource/p12_accesskey';
    const P12_CERTIFICATE = 'payment/chcybersource/general_p12_certificate';

    // namespaces defined by standard
    const WSU_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    const WSSE_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const SOAP_NS = 'http://schemas.xmlsoap.org/soap/envelope/';
    const DS_NS = 'http://www.w3.org/2000/09/xmldsig#';

    /**
     * @var string
     */
    private $wsdl = null;

    /**
     * @var string
     */
    private $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    /**
     * @var string
     */
    public $merchantId = null;

    /**
     * @var string
     */
    public $transactionKey = null;

    /**
     * @var ScopeConfigInterface
     */
    public $config;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \SoapClient $client
     */
    public $client;

    /**
     * @var string
     */
    protected $p12_accesskey;

    /**
     * @var string
     */
    protected $p12_certificate;

    protected $_ssl_options = array();
    protected $_timeout = 6000;

    /**
     * AbstractConnection constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @throws \Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->config = $scopeConfig;
        $this->logger = $logger;

        $this->handleWsdlEnvironment();
        $this->setUpCredentials();
        $this->initSoapClient();
    }

    /**
     * Initialize SOAP Client
     *
     * @return \SoapClient
     * @throws \Exception
     */
    public function initSoapClient()
    {
        try {
            if ($this->wsdl !== null) {

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $filesystem = $objectManager->get('\Magento\Framework\Filesystem');
                $certificateDir = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR)->getAbsolutePath('certificates/');
                $sslOptions = [
                    'SSL' => [
                        'KEY_ALIAS' => 'cybersource',
                        'KEY_FILE' => $this->p12_certificate,
                        'KEY_PASS' => $this->p12_accesskey,
                        'KEY_DIRECTORY' => $certificateDir,
                        'CONNECTION_TIMEOUT' => '6000'
                    ]
                ];
                
                if($certificateDir && $this->p12_certificate){
                    $soapClient = new DoRequest($this->logger, $this->wsdl, $sslOptions);
                    $this->client = $soapClient;
                }
                return $this->client;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
        return null;
    }

    public function setSoapClient(\SoapClient $client)
    {
        $this->client = $client;
    }

    public function getSoapClient()
    {
        return $this->client;
    }

    /**
     * Handle WSDL Environment to use correct webservice based on environment config
     */
    private function handleWsdlEnvironment()
    {
        $isTestMode = $this->config->getValue(
            self::IS_TEST_MODE_CONFIG_PATH,
            $this->storeScope
        );

        if ($isTestMode) {
            $this->wsdl = $this->config->getValue(
                self::TEST_WSDL_PATH,
                $this->storeScope
            );
        } else {
            $this->wsdl = $this->config->getValue(
                self::LIVE_WSDL_PATH,
                $this->storeScope
            );
        }
    }

    /**
     * Setup Credentials for webservice
     */
    private function setUpCredentials()
    {
        $this->p12_accesskey = $this->config->getValue(
            self::P12_ACCESSKEY,
            $this->storeScope
        );
        $this->p12_certificate = $this->config->getValue(
            self::P12_CERTIFICATE,
            $this->storeScope
        );
        $this->merchantId = $this->config->getValue(
            self::MERCHANT_ID_PATH,
            $this->storeScope
        );
    }

    /**
     * Setup Credentials for webservice
     *
     * @param string $bankTransferPaymentMethod
     */
    public function setBankTransferCredentials($bankTransferPaymentMethod = 'ideal')
    {
        $this->merchantId = $this->config->getValue('payment/cybersource_bank_transfer/' . $bankTransferPaymentMethod . '_merchant_id', $this->storeScope);
        $this->transactionKey = $this->config->getValue('payment/cybersource_bank_transfer/' . $bankTransferPaymentMethod . '_transaction_key', $this->storeScope);
    }

    public function setCredentialsByStore($storeId)
    {
        $this->p12_accesskey = $this->config->getValue(
            self::P12_ACCESSKEY,
            'store',
            $storeId
        );
        $this->p12_certificate = $this->config->getValue(
            self::P12_CERTIFICATE,
            'store',
            $storeId
        );
        $this->merchantId = $this->config->getValue(
            self::MERCHANT_ID_PATH,
            'store',
            $storeId
        );
    }
}