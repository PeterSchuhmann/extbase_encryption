<?php
namespace PS\ExtbaseEncryption\Hooks;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class processDatamapClass {

    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler &$pObj)
    {
        // deprecated

    }

}