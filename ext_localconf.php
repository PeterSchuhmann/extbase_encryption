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

