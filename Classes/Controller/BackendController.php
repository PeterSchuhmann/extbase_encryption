<?php

namespace PS\ExtbaseEncryption\Controller;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Belog\Domain\Model\Constraint;
use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Abstract class to show log entries from sys_log
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendController extends ActionController
{

    public function indexAction()
    {

        $value = '';
        $encrypted = '';
        if ($this->request->hasArgument('value')) {
            $value = $this->request->getArgument('value');

            $encryptor = Encryptor::init();

            if ($encryptor->isValueEncrypted($value)) {
                $encrypted = $encryptor->decrypt($value);
            }
            else {
                $encrypted = $encryptor->encrypt($value);
            }

        }

        $this->view->assign('value', $value);
        $this->view->assign('encrypted', $encrypted);



//        print_r($this->request->getArguments());
//        exit;

    }

}