<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}


/**
 * SignalSlot to convert old tablenames to new tablenames automaticly after installing
 */
$dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$dispatcher->connect(
    \TYPO3\CMS\Extbase\Persistence\Generic\Backend::class,
    'afterGettingObjectData',
    \PS\ExtbaseEncryption\Slot\ConvertData::class,
    'read'
);

$dispatcher->connect(
    \TYPO3\CMS\Extbase\Persistence\Generic\Backend::class,
    'endInsertObject',
    \PS\ExtbaseEncryption\Slot\ConvertData::class,
    'insert'
);

$dispatcher->connect(
    \TYPO3\CMS\Extbase\Persistence\Generic\Backend::class,
    'afterUpdateObject',
    \PS\ExtbaseEncryption\Slot\ConvertData::class,
    'update'
);


//\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
//    \TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class,
//    'recordPostRetrieval',
//    \TYPO3\CMS\Frontend\Aspect\FileMetadataOverlayAspect::class,
//    'languageAndWorkspaceOverlay'
//);



$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']['PS\ExtbaseEncryption\Hooks\DatabaseEditRow'] = array('depends' => array('TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow'));


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'][$_EXTKEY] = 'PS\\ExtbaseEncryption\\Hooks\\PageLayoutView';


// $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:extbase_encryption/Classes/Hooks/processDatamapClass.php:PS\ExtbaseEncryption\Hooks\processDatamapClass';

// $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']['table'] = 'class';


/**
 * authentication hooks
 */

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'][] = 'PS\\ExtbaseEncryption\\Hooks\\Authentication->postUserLookUp';


/**
 * felogin extend controller to make pw reset possible
 */

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Felogin\Controller\FrontendLoginController::class] = [
    'className' => PS\ExtbaseEncryption\Controller\FrontendLoginController::class
];


/**
 * CommandController
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \PS\ExtbaseEncryption\Command\DatabaseCommandController::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \PS\ExtbaseEncryption\Command\TableCommandController::class;

/**
 * enable fe_login encryption
 * add all properties you want to be encrypted (please keep in mind that all fields you encrypt will have a longer value string than before -> that is why there is no phone and fax (varchar 25) included out of the box. To use them you need to updated your ext_tables.sql and alter to text (best choice)
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login'] = [
    'enable' => false,
    'properties' => ['username', 'name', 'address', 'email']
];

// Add the service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService('extbase_encryption', 'auth', \PS\ExtbaseEncryption\AuthService::class, [
    'title' => 'extbase_encryption authentication',
    'description' => 'Authenticates users by using encrypted fields like username',
    'subtype' => 'processLoginDataFE',
    'available' => true,
    'priority' => 70,
    'quality' => 70,
    'os' => '',
    'exec' => '',
    // Do not put a dependency on openssh here or service loading will fail!
    'className' => \PS\ExtbaseEncryption\AuthService::class
]);



if (TYPO3_MODE == 'BE')    {
//    $TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array('EXT:' . $_EXTKEY . '/Classes/Cli/CommandLineController.php', '_CLI_user');
}