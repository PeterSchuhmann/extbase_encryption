<?php

namespace PS\ExtbaseEncryption\Controller;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FrontendLoginController extends \TYPO3\CMS\Felogin\Controller\FrontendLoginController
{

    /**
     * Shows the forgot password form
     *
     * @return string Content
     */
    protected function showForgot()
    {

        $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_FORGOT###');
        $subpartArray = ($linkpartArray = []);
        $postData = GeneralUtility::_POST($this->prefixId);
        if ($postData['forgot_email']) {

            // Get hashes for compare
            $postedHash = $postData['forgot_hash'];
            $hashData = $this->frontendController->fe_user->getKey('ses', 'forgot_hash');
            if ($postedHash === $hashData['forgot_hash']) {
                $userTable = $this->frontendController->fe_user->user_table;
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($userTable);
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

                $encryptor = Encryptor::init();

                $email = strtolower($this->piVars['forgot_email']);

                $row = $queryBuilder
                    ->select('*')
                    ->from($userTable)
                    ->where(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->orX(
                                $queryBuilder->expr()->eq(
                                    'email',
                                    $queryBuilder->createNamedParameter($email, \PDO::PARAM_STR)
                                ),
                                $queryBuilder->expr()->eq(
                                    'email',
                                    $queryBuilder->createNamedParameter($encryptor->encrypt($email), \PDO::PARAM_STR)
                                )
                            ),
                            $queryBuilder->expr()->orX(
                                $queryBuilder->expr()->eq(
                                    'username',
                                    $queryBuilder->createNamedParameter($email, \PDO::PARAM_STR)
                                ),
                                $queryBuilder->expr()->eq(
                                    'username',
                                    $queryBuilder->createNamedParameter($encryptor->encrypt($email), \PDO::PARAM_STR)
                                )
                            )
                        ),
                        $queryBuilder->expr()->in(
                            'pid',
                            $queryBuilder->createNamedParameter(
                                GeneralUtility::intExplode(',', $this->spid),
                                Connection::PARAM_INT_ARRAY
                            )
                        )
                    )
                    ->execute()
                    ->fetch();

                $error = null;
                if ($row) {

                    $row = $encryptor->decryptFeUserRow($row);

                    // Generate an email with the hashed link
                    $error = $this->generateAndSendHash($row);
                } elseif ($this->conf['exposeNonexistentUserInForgotPasswordDialog']) {
                    $error = $this->pi_getLL('ll_forgot_reset_message_error');
                }
                // Generate message
                if ($error) {
                    $markerArray['###STATUS_MESSAGE###'] = $this->cObj->stdWrap($error, $this->conf['forgotErrorMessage_stdWrap.']);
                } else {
                    $markerArray['###STATUS_MESSAGE###'] = $this->cObj->stdWrap(
                        $this->pi_getLL('ll_forgot_reset_message_emailSent'),
                        $this->conf['forgotResetMessageEmailSentMessage_stdWrap.']
                    );
                }
                $subpartArray['###FORGOT_FORM###'] = '';
            } else {
                // Wrong email
                $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('forgot_reset_message', $this->conf['forgotMessage_stdWrap.']);
                $markerArray['###BACKLINK_LOGIN###'] = '';
            }
        } else {
            $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('forgot_reset_message', $this->conf['forgotMessage_stdWrap.']);
            $markerArray['###BACKLINK_LOGIN###'] = '';
        }
        $markerArray['###BACKLINK_LOGIN###'] = $this->getPageLink(htmlspecialchars($this->pi_getLL('ll_forgot_header_backToLogin')), []);
        $markerArray['###STATUS_HEADER###'] = $this->getDisplayText('forgot_header', $this->conf['forgotHeader_stdWrap.']);
        $markerArray['###LEGEND###'] = htmlspecialchars($this->pi_getLL('legend', $this->pi_getLL('reset_password')));
        $markerArray['###ACTION_URI###'] = $this->getPageLink('', [$this->prefixId . '[forgot]' => 1], true);
        $markerArray['###EMAIL_LABEL###'] = htmlspecialchars($this->pi_getLL('your_email'));
        $markerArray['###FORGOT_PASSWORD_ENTEREMAIL###'] = htmlspecialchars($this->pi_getLL('forgot_password_enterEmail'));
        $markerArray['###FORGOT_EMAIL###'] = $this->prefixId . '[forgot_email]';
        $markerArray['###SEND_PASSWORD###'] = htmlspecialchars($this->pi_getLL('reset_password'));
        $markerArray['###DATA_LABEL###'] = htmlspecialchars($this->pi_getLL('ll_enter_your_data'));
        $markerArray = array_merge($markerArray, $this->getUserFieldMarkers());
        // Generate hash
        $hash = md5($this->generatePassword(3));
        $markerArray['###FORGOTHASH###'] = $hash;
        // Set hash in feuser session
        $this->frontendController->fe_user->setKey('ses', 'forgot_hash', ['forgot_hash' => $hash]);
        return $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
    }

}