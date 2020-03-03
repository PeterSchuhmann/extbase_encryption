<?php

namespace PS\ExtbaseEncryption\Hooks;

use PS\ExtbaseEncryption\Encryptor;

class CustomLabel {

    public function decryptLabel(&$parameters)
    {
        $encryptor = Encryptor::init();
        $parameters['title'] = $encryptor->decrypt($parameters['row']['username']);
    }

}