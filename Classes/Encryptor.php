<?php

namespace PS\ExtbaseEncryption;

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

}