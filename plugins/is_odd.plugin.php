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

	{{#is_odd variable}}
		is odd!
	{{/is_odd}}
	
	{{#is_odd variable}}
		is odd!
	{{else}}
		is not odd!
	{{/is_odd}}

*/

$plugin['is_odd'] = function($value,$options) {
	/* parse the "then" (fn) or the "else" (inverse) */
	$return = '';

	if ($value % 2) {
		$return = $options['fn']($options['_this']);
	} elseif ($options['inverse'] instanceof \Closure) {
		$return = $options['inverse']($options['_this']);
	}

	return $return;
};
