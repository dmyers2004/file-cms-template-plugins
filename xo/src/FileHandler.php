<?php
/**
 * XO
 *
 * File Based CMS
 * File Loading Functions
 *
 * This content is released under the MIT License (MIT)
 * Copyright (c) 2014 - 2019, Project Orange Box
 */

namespace xo;

/**
 *
 * @package XO
 * @author Don Myers
 * @copyright 2019
 * @license http://opensource.org/licenses/MIT MIT License
 * @link https://github.com/dmyers2004
 * @version v1.0.0
 * @filesource
 *
 */

class FileHandler
{
	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	protected $app;

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param $app
	 *
	 * @throws
	 * @return
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function __construct($app)
	{
		$this->app = &$app;
	}

	/* auto detect by extension */
	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function load(string $filename) /* mixed */
	{
		logMsg('Get "'.$filename.'".');

		$ext = pathinfo($filename, PATHINFO_EXTENSION);

		logMsg('Extension "'.$ext.'".');

		$data = [];

		switch ($ext) {
			case 'md':
				$data = $this->md($filename);
			break;
			case 'yaml':
				$data = $this->yaml($filename);
			break;
			case 'ini':
				$data = $this->ini($filename);
			break;
			case 'array':
				$data = $this->array($filename);
			break;
			case 'json':
				$data = $this->json($filename);
			break;
		}

		return $data;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function array(string $filename) : array
	{
		if (substr($filename, -6) == '.array') {
			$filename = substr($filename, 0, -6);
		}

		$filename = $this->cleanPath(ROOTPATH.'/'.$this->app->config('data path').'/'.$filename.'.array');

		logMsg('Get Array "'.$filename.'".');

		$array = '';

		if (file_exists($filename)) {
			$array = include $filename;
		}

		return $array;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function json(string $filename) : array
	{
		if (substr($filename, -5) == '.json') {
			$filename = substr($filename, 0, -5);
		}

		$filename = $this->cleanPath(ROOTPATH.'/'.$this->app->config('data path').'/'.$filename.'.json');

		logMsg('Get JSON "'.$filename.'".');

		$array = '';

		if (file_exists($filename)) {
			$array = json_decode(file_get_contents($filename), true);
		}

		return $array;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return string
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function md(string $filename) : string
	{
		if (substr($filename, -3) == '.md') {
			$filename = substr($filename, 0, -3);
		}

		$filename = $this->cleanPath(ROOTPATH.'/'.$this->app->config('data path').'/'.$filename.'.md');

		logMsg('Get Markdown "'.$filename.'".');

		$html = '';

		if (file_exists($filename)) {
			$html = \Michelf\Markdown::defaultTransform(file_get_contents($filename));
		}

		return $html;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function yaml(string $filename) : array
	{
		if (substr($filename, -5) == '.yaml') {
			$filename = substr($filename, 0, -5);
		}

		$filename = $this->cleanPath(ROOTPATH.'/'.$this->app->config('data path').'/'.$filename.'.yaml');

		logMsg('Get YAML "'.$filename.'".');

		$yaml = '';

		if (file_exists($filename)) {
			$yaml = yaml_parse(file_get_contents($filename));
		}

		return $yaml;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function ini(string $filename) : array
	{
		if (substr($filename, -4) == '.ini') {
			$filename = substr($filename, 0, -4);
		}

		$filename = $this->cleanPath(ROOTPATH.'/'.$this->app->config('data path').'/'.$filename.'.ini');

		logMsg('Get ini "'.$filename.'".');

		$ini = [];

		if (file_exists($filename)) {
			$ini = parse_ini_file($filename, true, INI_SCANNER_NORMAL);
		}

		return $ini;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $path
	 *
	 * @throws
	 * @return string
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function cleanPath(string $path) : string
	{
		return str_replace('//', '/', $path);
	}

} /* end class */
