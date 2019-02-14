<?php

/**
 *
 * Attach or retrieve instance of Application object
 *
 * @access global
 *
 * @param $application null
 *
 * @return application
 *
 */
if (!function_exists('app')) {
	function app($app=null)
	{
		global $application_instance_do_not_use;
		
		if ($app) {
			$application_instance_do_not_use = $app;
		}
	
		return $application_instance_do_not_use;
	}
}

/**
 *
 * Simple Write to log file
 *
 * @access global
 *
 * @param mutiple
 *
 * @return void
 *
 */
if (!function_exists('log_msg')) {
	function log_msg() : void
	{
		if (DEBUG) {
			$lines = '';
			
			foreach (func_get_args() as $msg) {
				$lines .= date('r').chr(9).$msg.PHP_EOL;
			}
			
			file_put_contents(ROOTPATH.'/debug.log', $lines, FILE_APPEND | LOCK_EX);
		}
	}
}

/**
 *
 * Wrapper to get application configuration values with optional default value
 *
 * @access global
 *
 * @param string $name
 * @param $default null
 *
 * @return mixed
 *
 */
if (!function_exists('config')) {
	function config(string $name, $default=null)
	{
		return app()->config($name, $default);
	}
}

/**
 * Write a string to a file with atomic uninterruptible
 *
 * @param string $filepath path to the file where to write the data
 * @param mixed $content the data to write
 *
 * @return int the number of bytes that were written to the file.
 */
if (!function_exists('atomic_file_put_contents')) {
	function atomic_file_put_contents(string $filepath, $content) : int
	{
		/* get the path where you want to save this file so we can put our file in the same file */
		$dirname = dirname($filepath);

		/* is the directory writeable */
		if (!is_writable($dirname)) {
			throw new Exception('atomic file put contents folder "'.$dirname.'" not writable');
		}

		/* create file with unique file name with prefix */
		$tmpfname = tempnam($dirname, 'afpc_');

		/* did we get a temporary filename */
		if ($tmpfname === false) {
			throw new Exception('atomic file put contents could not create temp file');
		}

		/* write to the temporary file */
		$bytes = file_put_contents($tmpfname, $content);

		/* did we write anything? */
		if ($bytes === false) {
			throw new Exception('atomic file put contents could not file put contents');
		}

		/* changes file permissions so I can read/write and everyone else read */
		if (chmod($tmpfname, 0644) === false) {
			throw new Exception('atomic file put contents could not change file mode');
		}

		/* move it into place - this is the atomic function */
		if (rename($tmpfname, $filepath) === false) {
			throw new Exception('atomic file put contents could not make atomic switch');
		}

		/* if it's cached we need to flush it out so the old one isn't loaded */
		remove_php_file_from_opcache($filepath);

		/* if log message function is loaded at this point log a debug entry */
		if (function_exists('log_message')) {
			log_message('debug', 'atomic_file_put_contents wrote '.$filepath.' '.$bytes.' bytes');
		}

		/* return the number of bytes written */
		return $bytes;
	}
}

/**
 * invalidate it if it's a cached script
 *
 * @param $fullpath
 *
 * @return
 *
 */
if (!function_exists('remove_php_file_from_opcache')) {
	function remove_php_file_from_opcache(string $filepath) : bool
	{
		$success = true;

		/* flush from the cache */
		if (function_exists('opcache_invalidate')) {
			$success = opcache_invalidate($filepath, true);
		} elseif (function_exists('apc_delete_file')) {
			$success = apc_delete_file($filepath);
		}

		return $success;
	}
}
