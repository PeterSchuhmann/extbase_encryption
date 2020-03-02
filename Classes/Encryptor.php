<?php

namespace PS\ExtbaseEncryption;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Class Encryptor
 * @package Resomedia\DoctrineEncryptBundle\Encryptors
 * @source https://github.com/Resomedia/DoctrineEncryptBundle/blob/master/Encryptors/Encryptor.php
 */
class Encryptor
{
    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var protocol
     */
    private $protocol;

    /**
     * @var vector
     */
    private $vector;

    /**
     * @var boolean
     */
    private $oldVersion;


    public static function init()
    {
        return new self(Config::get('encryption_secretKey'), Config::get('encryption_protocol'), Config::get('encryption_vector'));
    }

    /**
     * Must accept secret key for encryption
     * @param string $secretKey the encryption key
     * @param string $protocol
     * @param string $iv
     */
    public function __construct($secretKey, $protocol, $iv)
    {
        $this->secretKey = $secretKey;
        $this->protocol = $protocol;
        $this->vector = $iv;

        $this->oldVersion = (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 8007000);

        if (empty($this->secretKey) || empty($this->protocol) || empty($this->vector)) {
        	throw new \Exception('Error: no encryption config found. Please save settings in Extensionmanger');
		}
    }



    public function isValueEncrypted($data)
	{
		return (strpos($data, '[ENC]') !== false);
	}

    /**
     * Add <ENC> Tag for detect encrypted value
     * @param string $data Plain text to encrypt
     *
     * @throws \Exception
     *
     * @return string Encrypted text
     */
    public function encrypt($data)
    {
		if (!$this->isValueEncrypted($data)) {
			$value = openssl_encrypt($data, $this->protocol, $this->secretKey, 0, $this->vector);
		   if ($value === false) {
			   throw new \Exception('Impossible to crypt data: ' . $data);
		   }
		   $value = '[ENC]' . $value;
		}
		else {
			$value = $data;
		}

        return $value;
    }


    public function encryptRow($row, $table)
    {
        if(!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])) {
            throw new \Exception('Error: table "' . $table . '" was not found in GLOBALS SC_OPTIONS (please see readme for that)"');
        }

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table];

        $this->reflectionService = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
        $properties = $this->reflectionService->getClassPropertyNames($class);

        if (is_array($properties)) {
            foreach ($properties as $property) {
                $tags = $this->reflectionService->getPropertyTagsValues($class, $property);
                if (isSet($tags['encrypted'])) {
                    if (isSet($row[$property])) {
                        $row[$property] = $this->encrypt($row[$property]);
                    }
                }
            }
        }

        return $row;
    }



    public function encryptTable($table, $uid = 0)
    {
        if(!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])) {
            throw new \Exception('Error: table "' . $table . '" was not found in GLOBALS SC_OPTIONS (please see readme for that)"');
        }

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table];


        if ($uid > 0) {
            throw new \Exception('dont use decryptTable for single row descryption any more! please use decrytRow');
        }

        $this->reflectionService = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
        $properties = $this->reflectionService->getClassPropertyNames($class);

        $encrypted = array();
        if (is_array($properties)) {
            foreach ($properties as $property) {
                $tags = $this->reflectionService->getPropertyTagsValues($class, $property);
                if (isSet($tags['encrypted'])) {
                    $encrypted[] = $property;
                }
            }
        }

        if ($this->oldVersion) {

            $offset = 0;
            do {
                $affectedRows = 0;

                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $addWhere, '', 'uid asc', $offset. ',100');
                while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

                    var_dump($row['uid']);

                    foreach($encrypted as $property) {
                        $row[$property] = $this->encrypt($row[$property]);
                    }

                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid = ' . $row['uid'], $row);
                    $row = null;

                    $affectedRows++;
                }

                $offset += $affectedRows;
            } while ($affectedRows >= 100);
        }
        else {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $queryBuilder = $connectionPool->getConnectionForTable($table)->createQueryBuilder();
            $databaseConnection = $connectionPool->getConnectionForTable($table);

            $queryBuilder->select('*')->from($table);
            if ($uid > 0) {
                $queryBuilder->where($queryBuilder->expr()->eq('uid', $uid));
            }
            $statement = $queryBuilder->execute();
            while ($row = $statement->fetch()) {
                // Do something with that single row
                foreach($encrypted as $property) {
                    $row[$property] = $this->encrypt($row[$property]);
                }

                $databaseConnection->update(
                    $table,
                    $row,
                    array('uid' => $row['uid'])
                );

            }
        }

        return true;
    }

    /**
     * remove <ENC> Tag before to decrypt data
     * @param string $data Encrypted text
     *
     * @throws \Exception
     *
     * @return string Plain text
     */
    public function decrypt($data)
    {
        if ($this->isValueEncrypted($data)) {
            $value = openssl_decrypt(str_replace('[ENC]', '',$data), $this->protocol, $this->secretKey, 0, $this->vector);
            if ($value === false) {
                throw new \Exception('Impossible to decrypt data: ' . $data);
            }
        }
        else {
            $value = $data;
        }
        return $value;
    }

    public function isFieldEncrypted($field, $table)
    {
        if(!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])) {
            throw new \Exception('Error: table "' . $table . '" was not found in GLOBALS SC_OPTIONS (please see readme for that)"');
        }

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table];

        $this->reflectionService = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
        $properties = $this->reflectionService->getClassPropertyNames($class);

        if (!in_array($field, $properties)) {
            throw new \Exception('field "' . $field . '" not found as property of table "' . $table . '"');
        }

        $tags = $this->reflectionService->getPropertyTagsValues($class, $field);
        if (isSet($tags['encrypted'])) {
            return true;
        }

        return false;
    }

    public function decryptRow($row, $table)
    {
        if(!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])) {
            throw new \Exception('Error: table "' . $table . '" was not found in GLOBALS SC_OPTIONS (please see readme for that)"');
        }

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table];

        $this->reflectionService = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
        $properties = $this->reflectionService->getClassPropertyNames($class);

        if (is_array($properties)) {
            foreach ($properties as $property) {
                $tags = $this->reflectionService->getPropertyTagsValues($class, $property);
                if (isSet($tags['encrypted'])) {
                    if (isSet($row[$property])) {
                        $row[$property] = $this->decrypt($row[$property]);
                    }
                }
            }
        }

        return $row;
    }


    public function decryptTable($table, $uid = 0)
    {
        if(!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])) {
            throw new \Exception('Error: table "' . $table . '" was not found in GLOBALS SC_OPTIONS (please see readme for that)"');
        }

        if ($uid > 0) {
            throw new \Exception('dont use decryptTable for single row descryption any more! please use decrytRow');
        }

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table];


        $this->reflectionService = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
        $properties = $this->reflectionService->getClassPropertyNames($class);

        $encrypted = array();
        if (is_array($properties)) {
            foreach ($properties as $property) {
                $tags = $this->reflectionService->getPropertyTagsValues($class, $property);
                if (isSet($tags['encrypted'])) {
                    $encrypted[] = $property;
                }
            }
        }

        if ($this->oldVersion) {

            $offset = 0;
            do {
                $affectedRows = 0;

                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $addWhere, '', 'uid asc', $offset. ',100');
                while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

                    var_dump($row['uid']);

                    foreach($encrypted as $property) {
                        $row[$property] = $this->decrypt($row[$property]);
                    }

                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid = ' . $row['uid'], $row);
                    $row = null;

                    $affectedRows++;
                }

                $offset += $affectedRows;
            } while ($affectedRows >= 100);

        }
        else {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $queryBuilder = $connectionPool->getConnectionForTable($table)->createQueryBuilder();
            $databaseConnection = $connectionPool->getConnectionForTable($table);

            $queryBuilder->select('*')->from($table);
            if ($uid > 0) {
                $queryBuilder->where($queryBuilder->expr()->eq('uid', $uid));
            }
            $statement = $queryBuilder->execute();
            while ($row = $statement->fetch()) {
                // Do something with that single row
                foreach($encrypted as $property) {
                    $row[$property] = $this->decrypt($row[$property]);
                }

                $databaseConnection->update(
                    $table,
                    $row,
                    array('uid' => $row['uid'])
                );

            }
        }

        return true;

    }

}