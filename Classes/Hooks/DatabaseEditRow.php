<?php

namespace PS\ExtbaseEncryption\Hooks;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractDatabaseRecordProvider;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class DatabaseEditRow extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * Fetch existing record from database
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        if (isSet($result['databaseRow']) && count($result['databaseRow']) > 0) {

            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']) && count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes']) > 0) {

                foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'] as $table => $class) {
                    if ($result['tableName'] == $table) {

                        $this->reflectionService = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
                        $properties = $this->reflectionService->getClassPropertyNames($class);
                        if (!is_array($properties)) {
                            continue;
                        }

                        foreach($properties as $property) {
                            $tags = $this->reflectionService->getPropertyTagsValues($class, $property);
                            if (isSet($tags['encrypted'])) {

                                if (!isSet($result['databaseRow'][$property])) {
                                    continue;
                                }

                                $encryptor = Encryptor::init();

                                $value = $result['databaseRow'][$property];

                                try {
                                    $result['databaseRow'][$property] = $encryptor->decrypt($value);
                                } catch(\Exception $e) {
                                    $result['databaseRow'][$property] = $value;
                                }

                            }

                        }

                    }

                }

            }

            if ($result['tableName'] == 'fe_users' && $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] == true)
            {
                $properties = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['properties'] ?? [];

                $encryptor = Encryptor::init();

                foreach ($properties as $property) {
                    if (isSet($result['databaseRow'][$property])) {
                        $value = $result['databaseRow'][$property];
                        try {
                            $result['databaseRow'][$property] = $encryptor->decrypt($value);
                        } catch(\Exception $e) {
                            $result['databaseRow'][$property] = $value;
                        }
                    }
                }

            }


        }

        return $result;
    }

}