# Extbase Encryption Plugin for TYPO3

has been tested with TYPO3 8.7 only (sorry haven't had more time yet). 

## Install

1) install extension
2) set encryption key in extension manager settings (please update key, dont use same as example key)
3) add annotation @encrypted to your extbase class probperties

use and see the magic happen in the database :)

## Limitations

Important: please make sure that all fields you store in database are storded in a large datatype like text (not varchar !!!) because the encrypted string might be much longer than decrypted key. If the datatype is not big enough your data will corrupted and lost. 

limitations: Works with string vars only

Based on the symfony project: https://github.com/Resomedia/DoctrineEncryptBundle/blob/master/Encryptors/Encryptor.php



## Example:

```
/**
 * Contact
 */
class Contact extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

	/**
	 * hash
	 *
	 * @var string
	 * @encrypted
	 */
	protected $clientid;

	/**
	 * hash
	 *
	 * @var string
	 */
	protected $hash = '';

	/**
	 * gender
	 *
	 * @var int
	 */
	protected $gender = 0;

	/**
	 * firstname
	 *
	 * @var string
	 * @encrypted
	 */
	protected $firstname = '';

	/**
	 * lastname
	 *
	 * @var string
	 * @encrypted
	 */
	protected $lastname = '';
	
	...
	
```