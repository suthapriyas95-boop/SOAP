<?php
 
namespace CyberSource\SecureAcceptance\Gateway\Request\Flex;
 
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
 
class GenerateKeyRequest implements BuilderInterface
{
    const TYPE_MAP = [
        'AE' => 'AMEX',
        'DI' => 'DISCOVER',
        'JCB' => 'JCB',
        'MC' => 'MASTERCARD',
        'VI' => 'VISA',
        'MI' => 'MAESTRO',
        'DN' => 'DINERSCLUB'
    ];
 
    const ENCRYPTION_TYPE = 'RsaOaep256';
 
    protected $config;
 
    const CLIENT_VERSION = 'v2';
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
 
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager, \CyberSource\SecureAcceptance\Gateway\Config\Config $config)
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
    }
 
    /***
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $storeFullUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
 
        $urlComponents = parse_url($storeFullUrl);
 
        $allowedCard = explode(',', $this->config->getCcTypes());
        $allowedCardNetworks = [];
       
        foreach (self::TYPE_MAP as $cardType => $cardTypeName) {
            if (in_array($cardType, $allowedCard)) {
                $allowedCardNetworks[] = $cardTypeName;
            }
        }
 
        return [
            'encryptionType' => self::ENCRYPTION_TYPE,
            'targetOrigins' => [$urlComponents['scheme'] . '://' . $urlComponents['host']],
            'allowedCardNetworks' => $allowedCardNetworks,
            'clientVersion' => self::CLIENT_VERSION,
            'transientTokenResponseOptions' =>['includeCardPrefix'=> false] 
        ];
    }
}