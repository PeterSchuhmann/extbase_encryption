1) install extension
2) set encryption key in extension manager settings (please update key, dont use same as example key)
3) add annotation @encrypted to your extbase class probperties

Important: please make sure that all fields you store in database are storded in a large datatype like text (not varchar !!!) because the encrypted string might be much longer than decrypted key. If the datatype is not big enough your data will corrupted and lost. 

limitations: Works with string vars only

Based on the symfony project: https://github.com/Resomedia/DoctrineEncryptBundle/blob/master/Encryptors/Encryptor.php