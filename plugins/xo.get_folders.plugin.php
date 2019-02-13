<?php

return function($options) use (&$in) {
	$captured_folder = ($in['captured']['folder']) ? '/'.$in['captured']['folder'] : '';
	
	$folder = ROOTPATH.'/public/'.$options['hash']['folder'].$captured_folder.'/*';
	
	$in['folders'] = glob($folder,GLOB_ONLYDIR);
};