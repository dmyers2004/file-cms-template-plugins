<?php

return function($options) {
	$key = $options['hash']['option'];

	$pathinfo = pathinfo($options['_this']);
	
	return $pathinfo[$key];
};
