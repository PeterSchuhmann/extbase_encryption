<?php

namespace PS\ExtbaseEncryption;

use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
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

            $userTable = 'fe_users';

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($userTable);
//            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

            $queryBuilder
                ->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $encryptor = Encryptor::init();

            $username = strtolower($loginData['uname']);

            $row = $queryBuilder
                ->select('*')
                ->from($userTable)
                ->where(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->eq(
                                'email',
                                $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)
                            ),
                            $queryBuilder->expr()->eq(
                                'email',
                                $queryBuilder->createNamedParameter($encryptor->encrypt($username), \PDO::PARAM_STR)
                            )
                        ),
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->eq(
                                'username',
                                $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)
                            ),
                            $queryBuilder->expr()->eq(
                                'username',
                                $queryBuilder->createNamedParameter($encryptor->encrypt($username), \PDO::PARAM_STR)
                            )
                        )
                    )
                )
                ->execute()
                ->fetch();

            if ($row) {
                $encryptor = Encryptor::init();
                $loginData['uname'] = $encryptor->isValueEncrypted($row['username']) ? $encryptor->encrypt($username) : $username;
            }

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
