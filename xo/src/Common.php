<?php 

function app($application=null)
{
	global $app;
	
	if ($application) {
		$app = $application;
	}

	return $app;
}

if (!function_exists('log_msg'))
{
	function log_msg() : void
	{
		file_put_contents(ROOTPATH.'/debug.log',date('r').chr(9).trim(implode(PHP_EOL,func_get_args())).PHP_EOL,FILE_APPEND | LOCK_EX);
	}
}

if (!function_exists('config'))
{
	function config($name,$default=null)
	{
		return app()->config($name,$default);
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
	function atomic_file_put_contents(string $filepath,$content) : int
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
