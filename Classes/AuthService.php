<?php

namespace PS\ExtbaseEncryption;

use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 */
class AuthService extends AuthenticationService
{

    /**
     * Process the submitted credentials.
     * In this case decrypt the password if it is RSA encrypted.
     *
     * @param array $loginData Credentials that are submitted and potentially modified by other services
     * @param string $passwordTransmissionStrategy Keyword of how the password has been hashed or encrypted before submission
     * @return bool
     */
    public function processLoginData(array &$loginData, $passwordTransmissionStrategy)
    {

        if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] == true)
        {
            $encryptor = Encryptor::init();
            $loginData['uname'] = $encryptor->encrypt(strtolower($loginData['uname']));
        }

        return true;
    }

    /**
     * Initializes the service.
     *
     * @return bool
     */
    public function init()
    {
        return parent::init();
    }

}
