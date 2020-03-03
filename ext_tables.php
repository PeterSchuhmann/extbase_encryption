<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'PS.ExtbaseEncryption',
    '',
    'web',
    '',
    [
        'Backend' => 'index'
    ],
    [
        'access' => 'admin',
        'icon' => 'EXT:extbase_encryption/Resources/Public/Icons/key.svg',
        'labels' => 'LLL:EXT:extbase_encryption/Resources/Private/Language/locallang_mod.xlf',
    ]
);