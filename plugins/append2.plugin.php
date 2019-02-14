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

{{#append2 name="page.links"}}


{{/append2}}

$options['fn']() = block content

*/
$plugin['append2'] = function($options) {
	//var_dump($options);

	$section = $options['hash']['section'];
	$key = $options['hash']['key'];
	
	if ($section) {
		$options['_this'][$section][$key] .= $options['fn']();
	} else {
		$options['_this'][$key] .= $options['fn']();
	}
};
