<?php

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use Magento\Framework\ObjectManager\TMapFactory;

class ServiceRequest implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    const SERVICE_RUN_TRUE = 'true';
    const MOTO_TRANSACTION = 'moto';
    const AREA_CODE = \Magento\Framework\App\Area::AREA_ADMINHTML;

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface[]
     */
    private $builders;

    /**
     * @var string
     */
    private $serviceName;

    protected $_state;

    public function __construct(
        \Magento\Framework\App\State $state,
        TMapFactory $tmapFactory,
        string $serviceName,
        array $builders = []
    ) {
        $this->_state = $state;
        $this->serviceName = $serviceName;
        $this->builders = $tmapFactory->create([
            'array' => $builders,
            'type' => \Magento\Payment\Gateway\Request\BuilderInterface::class
        ]);    
    }


    /**
     * Builds SOAP Service Request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {

        $result = [];

        foreach ($this->builders as $builder) {
            $result = array_merge($result, $builder->build($buildSubject));
        }

        if($this->_state->getAreaCode() == self::AREA_CODE)
        {
            $result['commerceIndicator'] = self::MOTO_TRANSACTION;
        }

       $result['run'] = self::SERVICE_RUN_TRUE;  

       return [$this->serviceName => $result];
    }
}
