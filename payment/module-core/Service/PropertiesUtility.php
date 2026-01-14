<?php

namespace CyberSource\Core\Service;

use CyberSource\Core\Model\LoggerInterface;
use InvalidArgumentException;

class PropertiesUtility
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * PropertiesUtility constructor.
     * 
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Validates the file path by checking the directory and file.
     * 
     * @param array $settings
     * @return bool
     */
    public function isValidFilePath($settings = [])
    {
        // Validate that the settings contain the necessary keys
        $keyDirectory = $settings['KEY_DIRECTORY'] ?? '';
        $keyFile = $settings['KEY_FILE'] ?? '';

        if (!is_string($keyDirectory) || empty($keyDirectory)) {
            $this->logger->error("Key Directory value is missing or empty.");
        }

        if (!is_string($keyFile) || empty($keyFile)) {
            $this->logger->error("Key File value is missing or empty.");
        }

        // Build the full file path
        $filePath = rtrim($keyDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $keyFile;

        // Check if the file exists and is a valid file
        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->logger->error("Key Directory and Key File values are not valid or the file does not exist. File Path: $filePath");
        }

        return true;
    }

    /**
     * Returns the full file path.
     * 
     * @param array $settings
     * @return string
     */
    public function getFilePath($settings = [])
    {
        // Return the concatenated file path
        $keyDirectory = $settings['KEY_DIRECTORY'] ?? '';
        $keyFile = $settings['KEY_FILE'] ?? '';

        $filePath = rtrim($keyDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $keyFile;
        return $filePath;
    }

    /**
     * Retrieves the certificate password.
     * 
     * @param array $settings
     * @return string
     */
    public function getCertificatePassword($settings = [])
    {
        $keyPass = $settings['KEY_PASS'] ?? '';

        if (!is_string($keyPass) || empty($keyPass)) {
            $this->logger->error("Key Password value is missing or empty.");
        }
        return $keyPass;
    }
}
