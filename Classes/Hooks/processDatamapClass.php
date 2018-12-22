<?php
namespace PS\ExtbaseEncryption\Hooks;

use PS\ExtbaseEncryption\Encryptor;

class processDatamapClass {

    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler &$pObj)
    {
        if (
            is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']) &&
            count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']) > 0 &&
            isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])
        ) {
            $encryptor = Encryptor::init();
            $encryptor->encryptTable($table, $id);
        }

    }

}