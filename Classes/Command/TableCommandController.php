<?php

namespace PS\ExtbaseEncryption\Command;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TableCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{

    /**
     * info: encrypt a single table
     *
     * encrypt a single table
     *
     * @param string $table
     */
    public function encryptCommand($table)
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
     * info: decrypt a single table
     *
     * decrypt a single table
     *
     * @param string $table
     */
    public function decryptCommand($table)
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

