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


$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']['PS\ExtbaseEncryption\Hooks\DatabaseEditRow'] = array('depends' => array('TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow'));



// $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:extbase_encryption/Classes/Hooks/processDatamapClass.php:PS\ExtbaseEncryption\Hooks\processDatamapClass';

// $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']['table'] = 'class';


if (TYPO3_MODE == 'BE')    {
    $TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array('EXT:' . $_EXTKEY . '/Classes/Cli/CommandLineController.php', '_CLI_user');
}