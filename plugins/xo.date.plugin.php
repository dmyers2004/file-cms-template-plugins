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

	<div class="date">Posted on {{xo.date entry_date format="Y-m-d H:i:s"}}</div>

*/

$plugin['xo:date'] = function($arg1,$options) {
	$timestamp = strtotime($arg1);

	return date($options['hash']['format'],$timestamp);
};