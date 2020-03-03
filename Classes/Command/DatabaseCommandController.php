<?php

namespace PS\ExtbaseEncryption\Command;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{

    /**
     * info: encrypt the entire database
     *
     * encrypt the entire database
     *
     * @return void
     */
    public function encryptCommand()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] == true) {
            $this->encryptTable('fe_users');
        }

        foreach (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']) && $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'] as $table => $class) {
            $this->encryptTable($table);
        }
    }

    protected function encryptTable($table)
    {
        $this->cli_echo('encrypting table "' . $table . '"'.chr(10));

        $encryptor = Encryptor::init();

        try {
            if ($table == 'fe_users' && $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] == true) {
                $encryptor->encryptFeUserTable();
            }
            else {
                $encryptor->encryptTable($table);
            }

        } catch (\Exception $e) {
            $this->cli_echo($e->getMessage() . chr(10));
        }

        $this->cli_echo('...done!'.chr(10));
    }

    /**
     * info: decrypt the entire database
     *
     * decrypt the entire database
     *
     * @return void
     */
    public function decryptCommand()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] == true) {
            $this->decryptTable('fe_users');
        }

        foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'] as $table => $class) {
            $this->decryptTable($table);
        }
    }


    protected function decryptTable($table)
    {
        $this->cli_echo('decrypting table "' . $table . '"'.chr(10));

        $encryptor = Encryptor::init();

        try {
            if ($table == 'fe_users' && $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] == true) {
                $encryptor->decryptFeUserTable();
            }
            else {
                $encryptor->decryptTable($table);
            }

        } catch (\Exception $e) {
            $this->cli_echo($e->getMessage() . chr(10));
        }

        $this->cli_echo('...done!'.chr(10));
    }

    protected function cli_echo($text)
    {
        echo $text;
    }
}

