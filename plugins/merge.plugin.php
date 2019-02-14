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

*/
$plugin['merge'] = function($options) {
	foreach (['array','ini','json','yaml'] as $ext) {
		if (isset($options['hash'][$ext])) {
			$filename = $options['hash'][$ext].'.'.$ext;
			break;
		}
	}

	$data = (array)app()->file->get($filename);

	if (isset($data['template'])) {
		$template = $data['template'];
	} else {
		$template = $options['hash']['template'];
	}

	$template_html = app()->handlebars->get_partial($template);

	return app()->handlebars->parse_string($template_html,$data);
};