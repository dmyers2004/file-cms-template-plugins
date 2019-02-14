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
	
	{{xo.error}}
	{{xo.error status=405}}
	{{xo.error status=404 msg="Oh Darn!"}}

*/
$plugin['xo:error'] = function($options) {
	$msg = (isset($options['hash']['msg'])) ? $options['hash']['msg'] : 'Error!';
	$code = (isset($options['hash']['code'])) ? $options['hash']['code'] : 404;
	
	app()->error(['msg'=>$msg,'code'=>$code]);
};
