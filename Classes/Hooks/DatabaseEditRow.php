<?php

namespace PS\ExtbaseEncryption\Hooks;

use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractDatabaseRecordProvider;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class DatabaseEditRow extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{

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

			$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['extbase_encryption']);
			$encryptor = new Encryptor($extConf['encryption_secretKey'], $extConf['encryption_protocol'], $extConf['encryption_vector']);

			try {
				$result['recordTitle']= $encryptor->decrypt($result['recordTitle']);
			} catch(\Exception $e) {
				$result['recordTitle'] = $result['recordTitle'];
			}

			foreach($result['databaseRow'] as $key => $value) {
				if (is_string($value)) {
					try {
						$result['databaseRow'][$key] = $encryptor->decrypt($value);
					} catch(\Exception $e) {
						$result['databaseRow'][$key] = $value;
					}
				}
			}
		}

		return $result;
	}

}