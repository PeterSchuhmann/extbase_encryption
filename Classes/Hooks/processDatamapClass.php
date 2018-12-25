<?php
namespace PS\ExtbaseEncryption\Hooks;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class processDatamapClass {

    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler &$pObj)
    {
        if (
            is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']) &&
            count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']) > 0 &&
            isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])
        ) {
            $encryptor = Encryptor::init();

            $row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, intval($id));

            $row = $encryptor->encryptRow($row, $table);

            $oldVersion = (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 8007000);

            if ($oldVersion) {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid = ' . $id, $row);
            }
            else {
                // needs implementation
            }

        }

    }

}