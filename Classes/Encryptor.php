<?php

namespace PS\ExtbaseEncryption;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
			   throw new \Exception('Impossible to crypt data');
		   }
		   $value = '[ENC]' . $value;
		}
		else {
			$value = $data;
		}

        return $value;
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
				throw new \Exception('Impossible to decrypt data');
			}
		}
		else {
    		$value = $data;
		}
        return $value;
    }

    public function encryptTable($table, $uid = 0)
    {
        if(!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])) {
            throw new \Exception('Error: table "' . $table . '" was not found in GLOBALS SC_OPTIONS (please see readme for that)"');
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
            foreach($encrypted as $property) {
                $row[$property] = $this->encrypt($row[$property]);
            }

            $databaseConnection->update(
                $table,
                $row,
                array('uid' => $row['uid'])
            );

        }

        return true;
    }


    public function decryptTable($table, $uid = 0)
    {
        if(!isSet($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'][$table])) {
            throw new \Exception('Error: table "' . $table . '" was not found in GLOBALS SC_OPTIONS (please see readme for that)"');
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
            foreach($encrypted as $property) {
                $row[$property] = $this->decrypt($row[$property]);
            }

            $databaseConnection->update(
                $table,
                $row,
                array('uid' => $row['uid'])
            );

        }

        return true;

    }

}