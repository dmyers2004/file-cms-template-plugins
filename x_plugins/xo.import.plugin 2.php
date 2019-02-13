<?php

return function($arg1,$arg2,$options) use (&$in) {
	$data = app()->get_ini($arg1);

	foreach ($data as $name=>$value) {
		if ($arg2) {
			$in[$arg2][$name] = $value;
		} else {
			$in[$name] = $value;
		}
	}
};