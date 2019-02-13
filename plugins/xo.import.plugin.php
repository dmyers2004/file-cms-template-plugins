<?php
/*

$options =>
	[name] => lex_lowercase # helper name
	[hash] => Array # key value pair
		[size] => 123
		[fullname] => Don Myers
	[contexts] => ... # full context as object
	[_this] => Array # current loop context
		[name] => John
		[phone] => 933.1232
		[age] => 21
	['fn']($options['_this']) # if ??? - don't forget to send in the context
	['inverse']($options['_this']) # else ???- don't forget to send in the context

	{{xo:import "filename.ini"}}
	{{xo:import "examples/import.ini" "namespace"}}

*/
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