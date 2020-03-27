<?php

namespace PS\ExtbaseEncryption\Backend\FormDataProvider;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\Igel\Felogin\Hooks\Encryption;

class EditRow implements FormDataProviderInterface {

    /**
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if ($result['tableName'] == 'fe_users' && $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] == true)
        {
            $enryptor = Encryptor::init();

            $result['databaseRow'] = $enryptor->decryptFeUserRow($result['databaseRow']);
        }

        return $result;
    }

}