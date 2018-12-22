<?php

namespace PS\ExtbaseEncryption;

class Config
{

    public static function get($key = '')
    {
        $config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['extbase_encryption']);

        if ($key != '') {
            if (!isSet($config[$key])) {
                throw new \Exception('key "' . $key . '" not found in extbase_encryption config array (maybe update ext manager settings)');
            }

            return $config[$key];
        }
        else {
            return $config;
        }
    }

}