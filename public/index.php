<?php

/* Based off this file where is the root of our web application? */
define('ROOTPATH',realpath(__DIR__.'/../'));

/* Changes PHP's current directory */
chdir(ROOTPATH);

/* Load composer auto loader */
require 'vendor/autoload.php';

/* create Application, route and send output */
(new \xo\App('config.ini'))->route()->output(true);
