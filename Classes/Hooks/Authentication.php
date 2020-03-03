<?php

namespace PS\ExtbaseEncryption\Hooks;

use PS\ExtbaseEncryption\Encryptor;

class Authentication {

    /**
     * decrypt $GLOBALS['TSFE']->fe_user->user row before it can be used
     *
     * @param $_params
     */
    public function postUserLookUp($_params)
    {
        $piObj = $_params['pObj'];

        if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] == true)
        {
            $encryptor = Encryptor::init();
            $piObj->user = $encryptor->decryptFeUserRow($piObj->user);
        }

    }


}