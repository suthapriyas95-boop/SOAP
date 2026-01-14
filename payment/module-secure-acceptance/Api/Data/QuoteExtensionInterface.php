<?php

namespace CyberSource\SecureAcceptance\Api\Data;

interface QuoteExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    public function getClientLibrary();
    public function setClientLibrary($clientLibrary);

    public function getClientLibraryIntegrity();
    public function setClientLibraryIntegrity($clientLibraryIntegrity);
}
