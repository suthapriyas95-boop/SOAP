<?php

namespace CyberSource\SecureAcceptance\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use CyberSource\SecureAcceptance\Api\Data\QuoteExtensionInterface;

class QuoteExtension extends AbstractExtensibleModel implements QuoteExtensionInterface
{
    public function getClientLibrary()
    {
        return $this->_get('client_library');
    }

    public function setClientLibrary($clientLibrary)
    {
        return $this->setData('client_library', $clientLibrary);
    }

    public function getClientLibraryIntegrity()
    {
        return $this->_get('client_library_integrity');
    }

    public function setClientLibraryIntegrity($clientLibraryIntegrity)
    {
        return $this->setData('client_library_integrity', $clientLibraryIntegrity);
    }
}
