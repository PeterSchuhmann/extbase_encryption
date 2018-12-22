<?php

namespace PS\ExtbaseEncryption\Cli;

//if (!defined('TYPO3_cliMode'))  die('You cannot run this script directly!');


use PS\ExtbaseEncryption\Encryptor;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CommandLineController extends \TYPO3\CMS\Core\Controller\CommandLineController {

    /**
     * Constructor
     */
    function __construct () {

        // Running parent class constructor
        parent::__construct();

        $commands = array('database:encrypt [--all] [--table=tx_test_domain_model_test]', 'database:decrypt [--all] [--table=tx_test_domain_model_test]');

        // Setting help texts:
        $this->cli_help['name'] = 'extbase_encryption CLI scripts';
        $this->cli_help['synopsis'] = '###OPTIONS###';
        $this->cli_help['description'] = 'extbase_encryption CLI scripts class';
        $this->cli_help['examples'] = '/.../cli_dispatch.phpsh EXTKEY TASK';
        $this->cli_help['author'] = 'Peter Schuhmann <mail@peterschuhmann.de>, (c) 2018';
        $this->cli_help['available commands'] = implode(chr(10), $commands);
    }

    /**
     * CLI engine
     *
     * @param    array        Command line arguments
     * @return    string
     */
    function cli_main($argv) {

        $this->command = (isSet($this->cli_args['_DEFAULT'][1])) ? $this->cli_args['_DEFAULT'][1] : '';

        if (!$this->command){
            $this->cli_validateArgs();
            $this->cli_help();
            exit;
        }

        if ($this->command != '') {
            switch($this->command) {
                case 'database:encrypt':
                    $this->encryptDatabase();
                    break;
                case 'database:decrypt':
                    $this->decryptDatabase();
                break;
                case 'help':
                    $this->cli_help();
                    exit;
                    break;
                default:
                    $this->cli_echo('Error: TASK "'.$this->command.'" not found'.chr(10));
                    $this->cli_echo('Try "help" for available command listing'.chr(10));
                    exit;
            }
        }

        exit(0);
    }

    protected function encryptDatabase()
    {

        if (!(isSet($this->cli_args['--all']) || isSet($this->cli_args['--table']))) {
            $this->cli_echo('Error: you need to use --all (for all tables) or --table=<tablename> (for single table)'.chr(10));
            return;
        }

        if (isSet($this->cli_args['--all'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'] as $table => $class) {
                $this->encryptTable($table);
            }
        }
        else if (isSet($this->cli_args['--table'])) {
            $table = trim($this->cli_args['--table']);
            $this->encryptTable($table);
        }
        else {
            $this->cli_echo('Error: unknown option. please make sure to user --all or --table=<tablename> (without spaces!)'.chr(10));
        }
    }

    protected function encryptTable($table)
    {
        $this->cli_echo('encrypting table "' . $table . '"'.chr(10));

        $encryptor = Encryptor::init();

        try {
            $encryptor->encryptTable($table);
        } catch (\Exception $e) {
            $this->cli_echo($e->getMessage() . chr(10));
        }

        $this->cli_echo('...done!'.chr(10));
    }

    protected function decryptDatabase()
    {

        if (!(isSet($this->cli_args['--all']) || isSet($this->cli_args['--table']))) {
            $this->cli_echo('Error: you need to use --all (for all tables) or --table=<tablename> (for single table)'.chr(10));
            return;
        }

        if (isSet($this->cli_args['--all'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase_encryption']['classes'] as $table => $class) {
                $this->decryptTable($table);
            }
        }
        else if (isSet($this->cli_args['--table'])) {
            $table = trim($this->cli_args['--table']);
            $this->decryptTable($table);
        }
        else {
            $this->cli_echo('Error: unknown option. please make sure to user --all or --table=<tablename> (without spaces!)'.chr(10));
        }
    }


    protected function decryptTable($table)
    {
        $this->cli_echo('decrypting table "' . $table . '"'.chr(10));

        $encryptor = Encryptor::init();

        try {
            $encryptor->decryptTable($table);
        } catch (\Exception $e) {
            $this->cli_echo($e->getMessage() . chr(10));
        }

        $this->cli_echo('...done!'.chr(10));
    }

}

// Call the functionality
$cleanerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('PS\ExtbaseEncryption\Cli\CommandLineController');
$cleanerObj->cli_main($_SERVER['argv']);

?>