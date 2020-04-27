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


    public function encryptFeUserRow($row)
    {
        $table = 'fe_users';
        $uid = $row['uid'];

        if (!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']) || !is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login'])) {
            return $row;
        }

        if (isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable']) && $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] === true)
        {
            $updateRow = [];
            $properties = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['properties'] ?? [];

            try {
                foreach($properties as $property) {

                    if (($property == 'username' || $property == 'email') && !$this->isValueEncrypted($row[$property])) {
                        $row[$property] = strtolower($row[$property]);
                    }

                    $updateRow[$property] = $this->encrypt($row[$property]);
                }
            } catch(\Exception $e) {
                echo 'failed to encrypt property "' . $property . '" of record fe_users ' .$row['uid'] . ' (' . $e->getMessage() . ')' .chr(10);
            }

            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $databaseConnection = $connectionPool->getConnectionForTable($table);

            $databaseConnection->update(
                $table,
                $updateRow,
                array('uid' => $row['uid'])
            );

            return $row;

        }
        else {
            return $row;
        }


    }

    public function encryptFeUserTable()
    {
        $table = 'fe_users';

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getConnectionForTable($table)->createQueryBuilder();
        $databaseConnection = $connectionPool->getConnectionForTable($table);

        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder->select('*')
            ->from($table);
//            ->setMaxResults(20);

        $statement = $queryBuilder->execute();
        while ($row = $statement->fetch()) {

            $skip = false;

            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_encryption']['skipEncryptRecord'] ?? [] as $reference) {

                $params = [
                    'skip' => $skip,
                    'row' => $row,
                    'table' => $table
                ];

                if ($reference) {
                    $skip = GeneralUtility::callUserFunction($reference, $params, $this);
                }
            }


            if (!$skip) {
                $this->encryptFeUserRow($row);
            }

        }
    }

    public function decryptFeUserTable()
    {
        $table = 'fe_users';

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getConnectionForTable($table)->createQueryBuilder();
        $databaseConnection = $connectionPool->getConnectionForTable($table);

        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder->select('*')->from($table);

        $statement = $queryBuilder->execute();
        while ($row = $statement->fetch()) {

            $skip = false;

            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_encryption']['skipDecryptRecord'] ?? [] as $reference) {

                $params = [
                    'skip' => $skip,
                    'row' => $row,
                    'table' => $table
                ];

                if ($reference) {
                    $skip = GeneralUtility::callUserFunction($reference, $params, $this);
                }
            }

            if (!$skip) {

                try {
                    $row = $this->decryptFeUserRow($row);

                    $databaseConnection->update(
                        $table,
                        $row,
                        array('uid' => $row['uid'])
                    );
                } catch (\Exception $e) {
                    echo 'failed to decrypt row ' . $row['uid'] . ' with error: ' . $e->getMessage() . chr(10);
                }
            }

        }
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

            $skip = false;

            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_encryption']['skipEncryptRecord'] ?? [] as $reference) {

                $params = [
                    'skip' => $skip,
                    'row' => $row,
                    'table' => $table
                ];

                if ($reference) {
                    $skip = GeneralUtility::callUserFunction($reference, $params, $this);
                }
            }


            if (!$skip) {

                try {
                    foreach ($encrypted as $property) {
                        $row[$property] = $this->encrypt($row[$property]);
                    }
                } catch (\Exception $e) {
                    echo 'failed to encrypt row ' . $row['uid'] . ' with error: ' . $e->getMessage();
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

    public function decryptFeUserRow($row)
    {
        $table = 'fe_users';
        $uid = $row['uid'];

        if (!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']) || !is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login'])) {
            return $row;
        }

        if (isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable']) && $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['enable'] === true) {
            $properties = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['fe_login']['properties'] ?? [];

            foreach ($properties as $property) {
                if (isSet($row[$property])) {
                    try {
                        $row[$property] = $this->decrypt($row[$property]);
                    } catch (\Exception $e) {
                        throw new \Exception('failed to decrypt value "' . $row[$property] . '" for property "' . $property . '"');
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

            $skip = false;

            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_encryption']['skipDecryptRecord'] ?? [] as $reference) {

                $params = [
                    'skip' => $skip,
                    'row' => $row,
                    'table' => $table
                ];

                if ($reference) {
                    $skip = GeneralUtility::callUserFunction($reference, $params, $this);
                }
            }

            if (!$skip) {

                try {
                    foreach($encrypted as $property) {
                        $row[$property] = $this->decrypt($row[$property]);
                    }

                    $databaseConnection->update(
                        $table,
                        $row,
                        array('uid' => $row['uid'])
                    );
                } catch(\Exception $e) {
                    echo 'failed to decrypt row ' . $row['uid'] . ' with error: ' . $e->getMessage();
                }
            }

        }

        return true;
    }

}