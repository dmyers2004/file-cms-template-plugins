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

	in is a reference to the data array sent in
	
	{{xo.set name="age" value=title}}
*/

$plugin['xo:img'] = function($options) use (&$in) {
	return new \LightnCandy\SafeString('<img src="/assets/'.$options['hash']['name'].'.png" width="48" height="48">');
};