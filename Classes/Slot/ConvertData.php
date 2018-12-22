<?php

namespace PS\ExtbaseEncryption\Slot;

use PS\ExtbaseEncryption\Encryptor;

class ConvertData
{

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 * @inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 * @inject
	 */
	protected $persistenceManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @param array $data
	 * @return void
	 */
	public function read(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query, array $data)
	{
		$properties = $this->reflectionService->getClassPropertyNames($query->getSource()->getNodeTypeName());
		foreach($properties as $property) {
			$tags = $this->reflectionService->getPropertyTagsValues($query->getSource()->getNodeTypeName(), $property);
			if (isSet($tags['encrypted'])) {
				$propertyName = $this->convertProperty($property);
				foreach($data as $index => $row) {
					if (isSet($data[$index][$propertyName])) {

                        $encryptor = Encryptor::init();

//						$data[$index][$propertyName] = $encryptor->decrypt($data[$index][$propertyName]);
					}
				}

			}
		}

		return array($query, $data);
	}

	private function convertProperty($property)
	{
		for ($i = 0; $i < strlen($property); $i++) {
			if (ord($property[$i]) >= ord('A') && ord($property[$i]) <= ord('Z')) {
				$property = str_replace($property[$i], '_' . chr(ord($property[$i]) + 32), $property);
			}
		}

		return $property;
	}

	/**
	 * @param mixed $object
	 * @param array $data
	 * @return void
	 */
	public function insert($object)
	{
		$class = get_class($object);

		$properties = $this->reflectionService->getClassPropertyNames($class);
		foreach($properties as $property) {
			$tags = $this->reflectionService->getPropertyTagsValues($class, $property);
			if (isSet($tags['encrypted'])) {

                $encryptor = Encryptor::init();

				$object->_setProperty($property, $encryptor->encrypt($object->_getProperty($property)));
			}
		}

	}


	/**
	 * @param mixed $object
	 * @param array $data
	 * @return void
	 */
	public function update($object)
	{
		$class = get_class($object);

		$persist = false;
		$properties = $this->reflectionService->getClassPropertyNames($class);
		foreach($properties as $property) {
			$tags = $this->reflectionService->getPropertyTagsValues($class, $property);
			if (isSet($tags['encrypted'])) {

				$encryptor = Encryptor::init();

				$existingValue = $object->_getProperty($property);

				if (!$encryptor->isValueEncrypted($existingValue)) {
					$object->_setProperty($property, $encryptor->encrypt($existingValue));
					$persist = true;
				}

			}
		}

		if ($persist) {
			// no idea why this is necessary because it isn't for insert
			$this->persistenceManager->persistAll();
		}

	}

}