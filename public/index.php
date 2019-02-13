<?php

define('ROOTPATH',realpath(__DIR__.'/../'));

chdir(ROOTPATH);

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);

require 'vendor/autoload.php';

(new \xo\App('config.ini'))->route()->output(true);
