<?php

return function($arg1,$arg2,$options) use (&$in) {
	$in['folders'] = glob($options['hash']['folder'],GLOB_ONLYDIR);
};